<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use He4rt\Activity\Message\Enums\MembershipEventKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Reaction\Models\Reaction;
use He4rt\Activity\Retrospective\Slides\MessagesSlide;
use He4rt\Activity\Retrospective\Slides\NewMembersSlide;
use He4rt\Activity\Retrospective\Slides\ReactionsSlide;
use He4rt\Activity\Retrospective\Slides\TopMessageSlide;
use He4rt\Activity\Retrospective\Slides\VoiceBoardSlide;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Community\Retrospective\Contracts\CuratableSource;
use He4rt\Community\Retrospective\Contracts\MeasuresPerson;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\DTOs\ExclusionCandidate;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\PersonAccount;
use He4rt\Community\Retrospective\DTOs\PersonIdentity;
use He4rt\Community\Retrospective\DTOs\SlideDescriptor;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fonte Discord da retrospectiva. Mora no activity (dono do dado: Voice,
 * Message, Reaction, MembershipEvent), não em integration-discord. As tabelas
 * são grandes em prod (messages ~2GB), então TODA métrica é agregada em SQL
 * escopada pelo Period; nomes de exibição são resolvidos em PHP apenas para as
 * poucas pessoas do topo de cada ranking. Filtra por sent_at/occurred_at
 * (tempo do evento), nunca created_at.
 */
final class DiscordSource implements CuratableSource, MeasuresPerson, RetrospectiveSource
{
    /**
     * Teto das varreduras de curadoria: o picker mostra o topo do recorte, nunca
     * a tabela inteira (messages passa de 2GB em produção).
     */
    private const int CANDIDATE_LIMIT = 20;

    /**
     * Uma linha de voz é um evento de presença (joined/left); "entrada" é só o
     * joined. Contar tudo dobraria o número, porque toda entrada vira uma saída.
     */
    private const string JOINS = "COUNT(*) FILTER (WHERE state = 'joined')";

    private const int CHANNEL_LIMIT = 6;

    private const int PEOPLE_LIMIT = 8;

    public function key(): string
    {
        return 'discord';
    }

    public function label(): string
    {
        return 'Discord';
    }

    public function collect(Period $period, SourceFilters $filters): SourceResult
    {
        $messageTotals = $this->messageTotals($period, $filters);
        $chatters = $this->topChatters($period, $filters);
        $messageDays = $this->messagesByWeekday($period, $filters);
        $messageHours = $this->messagesByHour($period, $filters);
        $messagePeak = $this->peakMessageDay($period, $filters);

        $voiceTotals = $this->voiceTotals($period, $filters);
        $participants = $voiceTotals['participants'];
        $channels = $this->topVoiceChannels($period, $filters);
        $voicePeople = $this->topVoicePeople($period, $filters);
        $voiceHours = $this->voiceByHour($period, $filters);
        $voicePeak = $this->peakVoiceDay($period, $filters);

        $joins = $this->membershipEvents($period, $filters)
            ->where('kind', MembershipEventKind::UserJoin->value)
            ->count();
        $boosts = $this->membershipEvents($period, $filters)
            ->where('kind', 'like', 'boost%')
            ->count();

        $totalReactions = $this->totalReactions($period, $filters);
        $emojis = $this->topEmojis($period, $filters);
        $topMessages = $this->topMessages($period, $filters);

        return new SourceResult(
            key: $this->key(),
            label: $this->label(),
            headline: $this->headline($messageTotals['total'], $participants, $joins, $totalReactions),
            slides: $this->slides(
                voiceTotals: $voiceTotals,
                channels: $channels,
                voicePeople: $voicePeople,
                voiceHours: $voiceHours,
                voicePeak: $voicePeak,
                messageTotals: $messageTotals,
                messagePeak: $messagePeak,
                chatters: $chatters,
                messageDays: $messageDays,
                messageHours: $messageHours,
                joins: $joins,
                boosts: $boosts,
                totalReactions: $totalReactions,
                emojis: $emojis,
                topMessages: $topMessages,
            ),
        );
    }

    /**
     * @return list<SlideDescriptor>
     */
    public function slideCatalog(): array
    {
        return [
            new SlideDescriptor('discord.voice_board', 'Voz', 'Pessoas em call, XP e canais mais quentes'),
            new SlideDescriptor('discord.messages', 'Conversas', 'Volume de mensagens e quem mais falou'),
            new SlideDescriptor('discord.new_members', 'Novos membros', 'Entradas e boosts no recorte'),
            new SlideDescriptor('discord.reactions', 'Reações', 'Total e emojis mais usados'),
            new SlideDescriptor('discord.top_message', 'Destaque', 'As mensagens mais reagidas (mostra conteúdo)'),
        ];
    }

    /**
     * O que esta fonte sabe sobre UMA pessoa no recorte, para o slide da tag He4rt.
     *
     * Casa pelo id da identidade do Discord, que é a coluna que messages e voice
     * guardam. Sem conta do Discord não há o que medir — e devolver lista vazia é
     * a resposta correta, não um erro: a pessoa pode ter chegado ao deck pelo
     * GitHub.
     *
     * Passa pelos MESMOS builders do collect(), então hide_bots e exclusions valem
     * aqui também: alguém escondido do deck não reaparece com números no cartão.
     *
     * @return list<Metric>
     */
    public function measure(PersonIdentity $person, Period $period, SourceFilters $filters): array
    {
        $account = $person->account(IdentityProvider::Discord->value);

        if (!$account instanceof PersonAccount) {
            return [];
        }

        /** @var list<Metric> $metrics */
        $metrics = Cache::remember(
            'retrospective.measure.'.$this->key().'.'.$period->cacheKey().'.'.$this->filtersKey($filters).'.'.$account->identityId,
            now()->addMinutes(5),
            fn (): array => $this->personMetrics($account->identityId, $period, $filters),
        );

        return $metrics;
    }

    /**
     * @return list<ExclusionCandidate>
     */
    public function exclusionCandidates(Period $period): array
    {
        /** @var list<ExclusionCandidate> $candidates */
        $candidates = Cache::remember(
            'retrospective.candidates.'.$this->key().'.'.$period->cacheKey(),
            now()->addMinutes(5),
            fn (): array => [...$this->messageCandidates($period), ...$this->memberCandidates($period)],
        );

        return $candidates;
    }

    /**
     * As mensagens mais reagidas do recorte: são as que o deck exibe com conteúdo
     * (slide de destaque), então são as que precisam de curadoria — spam e scam
     * chegam ao topo por reação.
     *
     * @return list<ExclusionCandidate>
     */
    private function messageCandidates(Period $period): array
    {
        $rows = $this->messages($period, new SourceFilters())
            ->where('reactions_total', '>', 0)
            ->orderByDesc('reactions_total')
            ->limit(self::CANDIDATE_LIMIT)
            ->get(['id', 'external_identity_id', 'content', 'reactions_total']);

        $names = $this->displayNames($this->identityIds($rows));

        return array_values(
            $rows->map(fn (Message $row): ExclusionCandidate => ExclusionCandidate::item(
                ref: 'message:'.$row->id,
                label: (string) str($row->content)->limit(80),
                hint: ($names[$row->external_identity_id] ?? 'Anônimo').' · '.$row->reactions_total.' reações',
            ))->all(),
        );
    }

    /**
     * @return list<ExclusionCandidate>
     */
    private function memberCandidates(Period $period): array
    {
        $rows = $this->messages($period, new SourceFilters())
            ->groupBy('external_identity_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(self::CANDIDATE_LIMIT)
            ->get(['external_identity_id', DB::raw('COUNT(*) AS messages')]);

        $names = $this->displayNames($this->identityIds($rows));

        return array_values(
            $rows->map(fn (Message $row): ExclusionCandidate => ExclusionCandidate::person(
                ref: 'member:'.$row->external_identity_id,
                label: $names[$row->external_identity_id] ?? 'Anônimo',
                hint: $row->getAttribute('messages').' mensagens',
            ))->all(),
        );
    }

    private function headline(int $messages, int $participants, int $joins, int $reactions): HeadlineMetrics
    {
        $metrics = [];

        if ($messages > 0) {
            $metrics[] = new Metric('Mensagens', $messages);
        }

        if ($participants > 0) {
            $metrics[] = new Metric('Pessoas em call', $participants);
        }

        if ($joins > 0) {
            $metrics[] = new Metric('Novos membros', $joins);
        }

        if ($reactions > 0) {
            $metrics[] = new Metric('Reações', $reactions);
        }

        return new HeadlineMetrics($metrics);
    }

    /**
     * @param  array{participants: int, joins: int, xp: int, earners: int}  $voiceTotals
     * @param  list<array{name: string, joins: int, people: int, xp: int, rooms: int}>  $channels
     * @param  list<array{name: string, xp: int, joins: int, channels: int}>  $voicePeople
     * @param  list<array{hour: int, joins: int}>  $voiceHours
     * @param  array{date: string, joins: int}|null  $voicePeak
     * @param  array{total: int, with_reactions: int, pinned: int, people: int}  $messageTotals
     * @param  array{date: string, messages: int}|null  $messagePeak
     * @param  list<array{name: string, messages: int, xp: int, reactions: int}>  $chatters
     * @param  list<array{weekday: int, messages: int}>  $messageDays
     * @param  list<array{hour: int, messages: int}>  $messageHours
     * @param  list<array{name: string, count: int, custom: bool}>  $emojis
     * @param  list<array{content: string, author: string, reactions: int}>  $topMessages
     * @return list<Slide>
     */
    private function slides(
        array $voiceTotals,
        array $channels,
        array $voicePeople,
        array $voiceHours,
        ?array $voicePeak,
        array $messageTotals,
        ?array $messagePeak,
        array $chatters,
        array $messageDays,
        array $messageHours,
        int $joins,
        int $boosts,
        int $totalReactions,
        array $emojis,
        array $topMessages,
    ): array {
        $slides = [];

        if ($voiceTotals['participants'] > 0) {
            $slides[] = new VoiceBoardSlide(
                participants: $voiceTotals['participants'],
                joins: $voiceTotals['joins'],
                xp: $voiceTotals['xp'],
                earners: $voiceTotals['earners'],
                peak: $voicePeak,
                channels: $channels,
                people: $voicePeople,
                hours: $voiceHours,
            );
        }

        if ($messageTotals['total'] > 0) {
            $slides[] = new MessagesSlide(
                total: $messageTotals['total'],
                withReactions: $messageTotals['with_reactions'],
                pinned: $messageTotals['pinned'],
                people: $messageTotals['people'],
                peak: $messagePeak,
                chatters: $chatters,
                days: $messageDays,
                hours: $messageHours,
            );
        }

        if ($joins > 0 || $boosts > 0) {
            $slides[] = new NewMembersSlide($joins, $boosts);
        }

        if ($totalReactions > 0) {
            $slides[] = new ReactionsSlide($totalReactions, $emojis);
        }

        if ($topMessages !== []) {
            $slides[] = new TopMessageSlide($topMessages);
        }

        return $slides;
    }

    /**
     * As três métricas que descrevem uma pessoa no Discord: quanto ela falou,
     * quanto tempo ela sustentou call e quanta reação o que ela escreveu puxou.
     *
     * Métrica zerada não entra: "0 reações" ocupa espaço no cartão para dizer que
     * não há o que dizer.
     *
     * @return list<Metric>
     */
    private function personMetrics(string $identityId, Period $period, SourceFilters $filters): array
    {
        $messages = $this->messages($period, $filters)
            ->where('external_identity_id', $identityId)
            ->count();

        $reactions = (int) Reaction::query()
            ->where('reactable_type', 'message')
            ->whereIn(
                'reactable_id',
                $this->messages($period, $filters)->where('external_identity_id', $identityId)->select('id'),
            )
            ->sum('count');

        $voiceXp = $this->countOf(
            $this->voice($period, $filters)
                ->where('external_identity_id', $identityId)
                ->sum('obtained_experience'),
        );

        $metrics = [
            new Metric('Mensagens', $messages),
            new Metric('XP em call', $voiceXp),
            new Metric('Reações recebidas', $reactions),
        ];

        return array_values(array_filter(
            $metrics,
            static fn (Metric $metric): bool => $metric->value > 0,
        ));
    }

    /**
     * Identidade dos filtros para a chave de cache. Sem isso, mexer numa exclusion
     * e reabrir o builder em menos de cinco minutos mostraria o número anterior.
     */
    private function filtersKey(SourceFilters $filters): string
    {
        return md5(($filters->hideBots ? '1' : '0').'|'.implode(',', $filters->exclusions));
    }

    /**
     * Base de mensagens do recorte. hideBots derruba source_kind='bot' mas mantém
     * linhas históricas com source_kind nulo. As exclusions entram aqui (e não na
     * composição) porque mexem no dado: o que é excluído some dos slides e também
     * dos números (ADR-0001). Filtra pelo provider da identidade porque messages
     * agora também guarda o chat das lives (IdentityProvider::He4rtLives), que não
     * é Discord e não pode inflar a retrospectiva dele.
     *
     * @return Builder<Message>
     */
    private function messages(Period $period, SourceFilters $filters): Builder
    {
        $excludedMessages = $filters->refsWithPrefix('message:');

        return Message::query()
            ->whereBetween('sent_at', [$period->since, $period->until])
            ->whereHas('provider', fn (Builder $query): Builder => $query->where('provider', IdentityProvider::Discord))
            ->when(
                $filters->hideBots,
                fn (Builder $query): Builder => $query->where(function (Builder $inner): void {
                    $inner->whereNull('source_kind')
                        ->orWhere('source_kind', '!=', MessageSourceKind::Bot->value);
                }),
            )
            ->when(
                $excludedMessages !== [],
                fn (Builder $query): Builder => $query->whereNotIn('id', $excludedMessages),
            )
            ->unless(
                $this->excludedMembers($filters) === [],
                fn (Builder $query): Builder => $query->whereNotIn('external_identity_id', $this->excludedMembers($filters)),
            );
    }

    /**
     * @return Builder<Voice>
     */
    private function voice(Period $period, SourceFilters $filters): Builder
    {
        return Voice::query()
            ->whereBetween('occurred_at', [$period->since, $period->until])
            ->unless(
                $this->excludedMembers($filters) === [],
                fn (Builder $query): Builder => $query->whereNotIn('external_identity_id', $this->excludedMembers($filters)),
            );
    }

    /**
     * @return Builder<MembershipEvent>
     */
    private function membershipEvents(Period $period, SourceFilters $filters): Builder
    {
        return MembershipEvent::query()
            ->whereBetween('occurred_at', [$period->since, $period->until])
            ->unless(
                $this->excludedMembers($filters) === [],
                fn (Builder $query): Builder => $query->whereNotIn('external_identity_id', $this->excludedMembers($filters)),
            );
    }

    /**
     * @return list<string>
     */
    private function excludedMembers(SourceFilters $filters): array
    {
        return $filters->refsWithPrefix('member:');
    }

    /**
     * Números do topo do painel de conversas numa passada só — mesmo argumento
     * do voiceTotals(): messages é a maior tabela do banco e cada agregado a
     * mais seria outra varredura do mesmo recorte.
     *
     * @return array{total: int, with_reactions: int, pinned: int, people: int}
     */
    private function messageTotals(Period $period, SourceFilters $filters): array
    {
        $row = $this->messages($period, $filters)
            ->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(*) FILTER (WHERE reactions_total > 0) AS with_reactions')
            ->selectRaw('COUNT(*) FILTER (WHERE is_pinned) AS pinned')
            ->selectRaw('COUNT(DISTINCT external_identity_id) AS people')
            ->first();

        return [
            'total' => $this->countOf($row?->total),
            'with_reactions' => $this->countOf($row?->with_reactions),
            'pinned' => $this->countOf($row?->pinned),
            'people' => $this->countOf($row?->people),
        ];
    }

    /**
     * O dia mais falante do recorte, agrupado no fuso de exibição — ver
     * peakVoiceDay() para o porquê do fuso e do GROUP BY posicional.
     *
     * @return array{date: string, messages: int}|null
     */
    private function peakMessageDay(Period $period, SourceFilters $filters): ?array
    {
        $row = $this->messages($period, $filters)
            ->toBase()
            ->selectRaw('(sent_at AT TIME ZONE ?)::date AS day', [$this->displayTimezone()])
            ->selectRaw('COUNT(*) AS messages')
            ->groupByRaw('1')
            ->orderByRaw('2 DESC')
            ->first();

        $messages = $this->countOf($row?->messages);
        $day = $row?->day;

        if ($messages === 0 || (!is_string($day) && !$day instanceof DateTimeInterface)) {
            return null;
        }

        return [
            'date' => CarbonImmutable::parse($day)->format('d/m'),
            'messages' => $messages,
        ];
    }

    /**
     * Mensagens por dia da semana, no fuso de exibição, com as 7 posições
     * sempre presentes (ISO: 1 = segunda) — dia sem papo é uma barra zerada,
     * não uma barra ausente.
     *
     * @return list<array{weekday: int, messages: int}>
     */
    private function messagesByWeekday(Period $period, SourceFilters $filters): array
    {
        $counts = $this->messages($period, $filters)
            ->toBase()
            ->selectRaw('EXTRACT(ISODOW FROM sent_at AT TIME ZONE ?)::int AS weekday', [$this->displayTimezone()])
            ->selectRaw('COUNT(*) AS messages')
            // Ver peakVoiceDay(): agrupar pela posição evita o segundo placeholder.
            ->groupByRaw('1')
            ->pluck('messages', 'weekday');

        return array_map(
            static fn (int $weekday): array => ['weekday' => $weekday, 'messages' => (int) ($counts[$weekday] ?? 0)],
            range(1, 7),
        );
    }

    /**
     * Mensagens por hora do dia, no fuso de exibição, com as 24 posições sempre
     * presentes — o histograma é irmão do voiceByHour().
     *
     * @return list<array{hour: int, messages: int}>
     */
    private function messagesByHour(Period $period, SourceFilters $filters): array
    {
        $counts = $this->messages($period, $filters)
            ->toBase()
            ->selectRaw('EXTRACT(HOUR FROM sent_at AT TIME ZONE ?)::int AS hour', [$this->displayTimezone()])
            ->selectRaw('COUNT(*) AS messages')
            // Ver peakVoiceDay(): agrupar pela posição evita o segundo placeholder.
            ->groupByRaw('1')
            ->pluck('messages', 'hour');

        return array_map(
            static fn (int $hour): array => ['hour' => $hour, 'messages' => (int) ($counts[$hour] ?? 0)],
            range(0, 23),
        );
    }

    /**
     * Quem mais conversou: mensagens, XP tirado delas e quanta reação o que a
     * pessoa escreveu puxou. Só o topo resolve nome (uma query a mais para 8
     * linhas).
     *
     * @return list<array{name: string, messages: int, xp: int, reactions: int}>
     */
    private function topChatters(Period $period, SourceFilters $filters): array
    {
        $rows = $this->messages($period, $filters)
            ->groupBy('external_identity_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(8)
            ->get([
                'external_identity_id',
                DB::raw('COUNT(*) AS messages'),
                DB::raw('COALESCE(SUM(obtained_experience), 0) AS xp'),
                DB::raw('COALESCE(SUM(reactions_total), 0) AS reactions'),
            ]);

        $names = $this->displayNames($this->identityIds($rows));

        return array_values(
            $rows->map(fn (Message $row): array => [
                'name' => $names[$row->external_identity_id] ?? 'Anônimo',
                'messages' => (int) $row->getAttribute('messages'),
                'xp' => (int) $row->getAttribute('xp'),
                'reactions' => (int) $row->getAttribute('reactions'),
            ])->all(),
        );
    }

    /**
     * As arenas do recorte: canais ordenados por entrada, com quanta gente passou,
     * quanto XP saiu dali e quantas SALAS o nome representa.
     *
     * Salas temporárias nascem e morrem com o mesmo nome (channel_id muda,
     * channel_name não), então agrupar por nome é o que junta "Trabalho" numa
     * linha só — e o COUNT DISTINCT do id é o que conta quantas foram.
     *
     * @return list<array{name: string, joins: int, people: int, xp: int, rooms: int}>
     */
    private function topVoiceChannels(Period $period, SourceFilters $filters): array
    {
        return array_values(
            $this->voice($period, $filters)
                ->whereNotNull('channel_name')
                ->groupBy('channel_name')
                ->orderByRaw(self::JOINS.' DESC')
                ->limit(self::CHANNEL_LIMIT)
                ->get([
                    'channel_name',
                    DB::raw(self::JOINS.' AS joins'),
                    DB::raw('COUNT(DISTINCT external_identity_id) AS people'),
                    DB::raw('COUNT(DISTINCT channel_id) AS rooms'),
                    DB::raw('COALESCE(SUM(obtained_experience), 0) AS xp'),
                ])
                ->map(fn (Voice $row): array => [
                    'name' => $row->channel_name,
                    'joins' => (int) $row->getAttribute('joins'),
                    'people' => (int) $row->getAttribute('people'),
                    'xp' => (int) $row->getAttribute('xp'),
                    'rooms' => (int) $row->getAttribute('rooms'),
                ])
                ->all(),
        );
    }

    /**
     * Números do topo do painel numa passada só: a tabela é grande e cada
     * agregado a mais seria outra varredura do mesmo recorte.
     *
     * @return array{participants: int, joins: int, xp: int, earners: int}
     */
    private function voiceTotals(Period $period, SourceFilters $filters): array
    {
        $row = $this->voice($period, $filters)
            ->toBase()
            ->selectRaw('COUNT(DISTINCT external_identity_id) AS participants')
            ->selectRaw(self::JOINS.' AS joins')
            ->selectRaw('COALESCE(SUM(obtained_experience), 0) AS xp')
            ->selectRaw('COUNT(DISTINCT external_identity_id) FILTER (WHERE obtained_experience > 0) AS earners')
            ->first();

        return [
            'participants' => $this->countOf($row?->participants),
            'joins' => $this->countOf($row?->joins),
            'xp' => $this->countOf($row?->xp),
            'earners' => $this->countOf($row?->earners),
        ];
    }

    /**
     * Um agregado cru, tipado. `first()` sobre a query base devolve stdClass, e o
     * driver não promete tipo nenhum nas propriedades dele: o Postgres manda COUNT
     * e SUM como string, e um recorte sem linha nenhuma manda null.
     *
     * Zero para o que não for número é a leitura honesta de um agregado ausente —
     * ninguém falou em voz naquele recorte.
     */
    private function countOf(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * O dia mais movimentado do recorte. A data é agrupada no fuso de exibição:
     * agrupar em UTC jogaria a madrugada de Brasília para o dia seguinte.
     *
     * @return array{date: string, joins: int}|null
     */
    private function peakVoiceDay(Period $period, SourceFilters $filters): ?array
    {
        $row = $this->voice($period, $filters)
            ->toBase()
            ->selectRaw('(occurred_at AT TIME ZONE ?)::date AS day', [$this->displayTimezone()])
            ->selectRaw(self::JOINS.' AS joins')
            // Agrupa e ordena por POSIÇÃO: repetir a expressão no GROUP BY criaria um
            // segundo placeholder, e o Postgres não assume que dois parâmetros
            // carregam o mesmo valor — passaria a exigir occurred_at no GROUP BY.
            ->groupByRaw('1')
            ->orderByRaw('2 DESC')
            ->first();

        $joins = $this->countOf($row?->joins);
        $day = $row?->day;

        // Sem entrada nenhuma não há pico. E dia que não se consegue ler também
        // não é pico: `parse('')` devolveria AGORA, e o deck anunciaria hoje como
        // o dia mais movimentado do recorte.
        if ($joins === 0 || (!is_string($day) && !$day instanceof DateTimeInterface)) {
            return null;
        }

        return [
            'date' => CarbonImmutable::parse($day)->format('d/m'),
            'joins' => $joins,
        ];
    }

    /**
     * Quem mais viveu no voice: XP, entradas e em quantos canais distintos a
     * pessoa apareceu. Só o topo resolve nome (uma query a mais para 12 linhas).
     *
     * @return list<array{name: string, xp: int, joins: int, channels: int}>
     */
    private function topVoicePeople(Period $period, SourceFilters $filters): array
    {
        $rows = $this->voice($period, $filters)
            ->groupBy('external_identity_id')
            ->orderByRaw('COALESCE(SUM(obtained_experience), 0) DESC')
            ->limit(self::PEOPLE_LIMIT)
            ->get([
                'external_identity_id',
                DB::raw('COALESCE(SUM(obtained_experience), 0) AS xp'),
                DB::raw(self::JOINS.' AS joins'),
                DB::raw('COUNT(DISTINCT channel_name) AS channels'),
            ]);

        $names = $this->displayNames(
            array_values($rows->map(fn (Voice $row): string => $row->external_identity_id)->all()),
        );

        return array_values(
            $rows->map(fn (Voice $row): array => [
                'name' => $names[$row->external_identity_id] ?? 'Anônimo',
                'xp' => (int) $row->getAttribute('xp'),
                'joins' => (int) $row->getAttribute('joins'),
                'channels' => (int) $row->getAttribute('channels'),
            ])->all(),
        );
    }

    /**
     * Entradas por hora do dia, no fuso de exibição, com as 24 posições sempre
     * presentes — a view desenha um histograma, e hora sem movimento é uma barra
     * zerada, não uma barra ausente.
     *
     * @return list<array{hour: int, joins: int}>
     */
    private function voiceByHour(Period $period, SourceFilters $filters): array
    {
        $counts = $this->voice($period, $filters)
            ->toBase()
            ->selectRaw('EXTRACT(HOUR FROM occurred_at AT TIME ZONE ?)::int AS hour', [$this->displayTimezone()])
            ->selectRaw(self::JOINS.' AS joins')
            // Ver peakVoiceDay(): agrupar pela posição evita o segundo placeholder.
            ->groupByRaw('1')
            ->pluck('joins', 'hour');

        return array_map(
            static fn (int $hour): array => ['hour' => $hour, 'joins' => (int) ($counts[$hour] ?? 0)],
            range(0, 23),
        );
    }

    private function displayTimezone(): string
    {
        $timezone = config('app.display_timezone');

        return is_string($timezone) ? $timezone : 'UTC';
    }

    private function totalReactions(Period $period, SourceFilters $filters): int
    {
        return (int) Reaction::query()
            ->where('reactable_type', 'message')
            ->whereIn('reactable_id', $this->messages($period, $filters)->select('id'))
            ->sum('count');
    }

    /**
     * @return list<array{name: string, count: int, custom: bool}>
     */
    private function topEmojis(Period $period, SourceFilters $filters): array
    {
        return array_values(
            Reaction::query()
                ->where('reactable_type', 'message')
                ->whereIn('reactable_id', $this->messages($period, $filters)->select('id'))
                ->groupBy('emoji_key', 'emoji_name')
                ->orderByRaw('SUM("count") DESC')
                ->limit(10)
                ->get([
                    'emoji_key',
                    'emoji_name',
                    DB::raw('MAX(emoji_id) AS emoji_id'),
                    DB::raw('SUM("count") AS total'),
                ])
                ->map(fn (Reaction $row): array => [
                    'name' => $row->emoji_name ?? $row->emoji_key,
                    'count' => (int) $row->getAttribute('total'),
                    'custom' => $row->getAttribute('emoji_id') !== null,
                ])
                ->all(),
        );
    }

    /**
     * @return list<array{content: string, author: string, reactions: int}>
     */
    private function topMessages(Period $period, SourceFilters $filters): array
    {
        $rows = $this->messages($period, $filters)
            ->where('reactions_total', '>', 0)
            ->orderByDesc('reactions_total')
            ->limit(3)
            ->get(['id', 'external_identity_id', 'content', 'reactions_total']);

        $names = $this->displayNames($this->identityIds($rows));

        return array_values(
            $rows->map(fn (Message $row): array => [
                'content' => (string) str($row->content)->limit(160),
                'author' => $names[$row->external_identity_id] ?? 'Anônimo',
                'reactions' => $row->reactions_total,
            ])->all(),
        );
    }

    /**
     * @param  Collection<int, Message>  $rows
     * @return list<string>
     */
    private function identityIds(Collection $rows): array
    {
        return array_values($rows->map(fn (Message $row): string => $row->external_identity_id)->all());
    }

    /**
     * Resolve o nome de exibição do Discord para os external_identity_id do topo
     * de um ranking: o username do Discord (metadata), senão o id externo. Só as
     * poucas pessoas do topo entram aqui, então a query é barata.
     *
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    private function displayNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return ExternalIdentity::query()
            ->whereIn('id', array_unique($ids))
            ->get()
            ->mapWithKeys(function (ExternalIdentity $identity): array {
                $metadata = $identity->metadata ?? [];
                $username = is_string($metadata['username'] ?? null) ? $metadata['username'] : null;

                return [$identity->id => $username ?? $identity->external_account_id ?? 'Anônimo'];
            })
            ->all();
    }
}
