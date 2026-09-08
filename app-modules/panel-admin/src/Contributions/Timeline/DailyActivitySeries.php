<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Timeline;

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\PanelAdmin\Contributions\Timeline\DTOs\GithubDay;
use He4rt\PanelAdmin\Contributions\Timeline\DTOs\MessageDay;
use He4rt\PanelAdmin\Contributions\Timeline\DTOs\TimelineDay;
use He4rt\PanelAdmin\Contributions\Timeline\DTOs\TimelineMeta;
use He4rt\PanelAdmin\Contributions\Timeline\DTOs\TimelineSeries;
use He4rt\PanelAdmin\Contributions\Timeline\DTOs\TimelineType;
use He4rt\PanelAdmin\Contributions\Timeline\DTOs\VoiceDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use stdClass;

/**
 * Séries diárias das três fontes da linha do tempo, uma query agregada por fonte.
 *
 * `messages` passa de 3M linhas, então nada é hidratado nem contado em PHP: cada
 * fonte devolve no máximo uma linha por dia. As três filtram pela coluna de tempo
 * do evento (`sent_at` / `occurred_at`), nunca por `created_at`.
 */
final readonly class DailyActivitySeries
{
    /**
     * Colunas do streamgraph, na ordem em que o deck as nomeia. A chave é o campo
     * que o componente lê no dia; o enum é quem sabe o valor gravado no lake.
     *
     * @var array<string, array{type: ContributionType, label: string}>
     */
    private const array TYPES = [
        'prs' => ['type' => ContributionType::Pr, 'label' => 'PRs'],
        'reviews' => ['type' => ContributionType::Review, 'label' => 'reviews'],
        'commits' => ['type' => ContributionType::Commit, 'label' => 'commits'],
        'issues' => ['type' => ContributionType::Issue, 'label' => 'issues'],
        'comments' => ['type' => ContributionType::Comment, 'label' => 'comments'],
        'reviewComments' => ['type' => ContributionType::ReviewComment, 'label' => 'review comments'],
    ];

    public function lastDays(int $days): TimelineSeries
    {
        $timezone = $this->displayTimezone();
        $until = CarbonImmutable::now($timezone)->endOfDay();

        return $this->between($until->subDays($days - 1)->startOfDay(), $until);
    }

    public function between(CarbonImmutable $since, CarbonImmutable $until): TimelineSeries
    {
        $timezone = $this->displayTimezone();

        $github = $this->githubByDay($since, $until, $timezone);
        $messages = $this->messagesByDay($since, $until, $timezone);
        $voice = $this->voiceByDay($since, $until, $timezone);

        $days = [];

        for ($date = $since->startOfDay(); $date->lessThanOrEqualTo($until); $date = $date->addDay()) {
            $key = $date->toDateString();

            $days[] = new TimelineDay(
                date: $date,
                github: $github[$key] ?? $this->emptyGithubDay(),
                messages: $messages[$key] ?? new MessageDay(messages: 0, people: 0, xp: 0),
                voice: $voice[$key] ?? new VoiceDay(sessions: 0, people: 0, xp: 0),
            );
        }

        return new TimelineSeries(
            meta: new TimelineMeta(
                since: $since->startOfDay(),
                until: $until->startOfDay(),
                dataUntil: $this->dataUntil([$github, $messages, $voice], $since, $until, $timezone),
                timezone: $timezone,
            ),
            days: $days,
            types: $this->types($days),
        );
    }

    /**
     * Um único passe pela tabela: os seis tipos viram agregados condicionais em vez
     * de seis COUNTs separados.
     *
     * @return array<string, GithubDay>
     */
    private function githubByDay(CarbonImmutable $since, CarbonImmutable $until, string $timezone): array
    {
        $query = $this->daily(GithubContribution::query(), 'occurred_at', $since, $until, $timezone)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(DISTINCT actor_login) AS people');

        foreach (self::TYPES as $key => $type) {
            $query->selectRaw("COUNT(*) FILTER (WHERE type = ?) AS {$this->column($key)}", [$type['type']->value]);
        }

        // Mesma regra do GithubSource: `[bot]` no login ou `is_bot` no payload.
        $query->whereRaw("actor_login NOT LIKE '%[bot]'")
            ->whereRaw("metadata->>'is_bot' IS DISTINCT FROM 'true'");

        return $this->keyByDay($query->get(), fn (array $row): GithubDay => new GithubDay(
            total: $this->int($row['total'] ?? null),
            prs: $this->int($row['prs'] ?? null),
            reviews: $this->int($row['reviews'] ?? null),
            commits: $this->int($row['commits'] ?? null),
            issues: $this->int($row['issues'] ?? null),
            comments: $this->int($row['comments'] ?? null),
            reviewComments: $this->int($row['review_comments'] ?? null),
            people: $this->int($row['people'] ?? null),
        ));
    }

    /**
     * @return array<string, MessageDay>
     */
    private function messagesByDay(CarbonImmutable $since, CarbonImmutable $until, string $timezone): array
    {
        $query = $this->daily(Message::query(), 'sent_at', $since, $until, $timezone)
            ->selectRaw('COUNT(*) AS messages')
            ->selectRaw('COUNT(DISTINCT external_identity_id) AS people')
            ->selectRaw('COALESCE(SUM(obtained_experience), 0) AS xp')
            // Mensagem sem `source_kind` é anterior à coluna: conta como gente.
            ->whereRaw('(source_kind IS NULL OR source_kind <> ?)', [MessageSourceKind::Bot->value]);

        return $this->keyByDay($query->get(), fn (array $row): MessageDay => new MessageDay(
            messages: $this->int($row['messages'] ?? null),
            people: $this->int($row['people'] ?? null),
            xp: $this->int($row['xp'] ?? null),
        ));
    }

    /**
     * @return array<string, VoiceDay>
     */
    private function voiceByDay(CarbonImmutable $since, CarbonImmutable $until, string $timezone): array
    {
        // Uma linha de voz é um evento de presença: contar tudo dobraria o número,
        // porque toda entrada vira uma saída. Sessão é só o `joined`.
        $query = $this->daily(Voice::query(), 'occurred_at', $since, $until, $timezone)
            ->selectRaw("COUNT(*) FILTER (WHERE state = 'joined') AS sessions")
            ->selectRaw('COUNT(DISTINCT external_identity_id) AS people')
            ->selectRaw('COALESCE(SUM(obtained_experience), 0) AS xp');

        return $this->keyByDay($query->get(), fn (array $row): VoiceDay => new VoiceDay(
            sessions: $this->int($row['sessions'] ?? null),
            people: $this->int($row['people'] ?? null),
            xp: $this->int($row['xp'] ?? null),
        ));
    }

    /**
     * O dia sai no fuso de exibição: agrupar em UTC jogaria a madrugada de Brasília
     * para o dia seguinte. `groupByRaw('1')` agrupa por POSIÇÃO — repetir a expressão
     * criaria um segundo placeholder, e o Postgres não assume que dois parâmetros
     * carregam o mesmo valor.
     *
     * @param  Builder<covariant Model>  $query
     * @param  literal-string  $column
     */
    private function daily(Builder $query, string $column, CarbonImmutable $since, CarbonImmutable $until, string $timezone): QueryBuilder
    {
        return $query->toBase()
            ->selectRaw("({$column} AT TIME ZONE ?)::date AS day", [$timezone])
            ->whereBetween($column, [$since, $until])
            ->groupByRaw('1');
    }

    /**
     * A linha crua do Postgres vira array antes de ser lida: `stdClass` não tem
     * forma declarada, então cada coluna seria um acesso a propriedade indefinida.
     *
     * @template TValue
     *
     * @param  Collection<int, stdClass>  $rows
     * @param  callable(array<array-key, mixed>): TValue  $make
     * @return array<string, TValue>
     */
    private function keyByDay(Collection $rows, callable $make): array
    {
        $keyed = [];

        foreach ($rows as $row) {
            $columns = get_object_vars($row);
            $day = $columns['day'] ?? null;

            if (!is_string($day)) {
                continue;
            }

            $keyed[CarbonImmutable::parse($day)->toDateString()] = $make($columns);
        }

        return $keyed;
    }

    /**
     * Até onde a ingestão chegou de verdade. É o menor "último dia com dado" entre
     * as fontes que produziram alguma coisa no recorte — uma fonte parada hachura o
     * rabo da linha do tempo em vez de anunciar zeros como se fossem dias calmos.
     *
     * Fonte vazia no período inteiro não entra na conta: isso é pipeline quebrado,
     * não atraso, e derrubaria o recorte inteiro para o dia anterior ao início.
     *
     * @param  list<array<string, mixed>>  $sources
     */
    private function dataUntil(array $sources, CarbonImmutable $since, CarbonImmutable $until, string $timezone): CarbonImmutable
    {
        $lastDays = [];

        foreach ($sources as $byDay) {
            $days = array_keys($byDay);

            if ($days === []) {
                continue;
            }

            $lastDays[] = CarbonImmutable::parse(max($days), $timezone)->startOfDay();
        }

        if ($lastDays === []) {
            return $since->startOfDay()->subDay();
        }

        return min($lastDays)->min($until->startOfDay());
    }

    /**
     * @param  list<TimelineDay>  $days
     * @return list<TimelineType>
     */
    private function types(array $days): array
    {
        $types = [];

        foreach (self::TYPES as $key => $type) {
            $types[] = new TimelineType(
                key: $key,
                label: $type['label'],
                count: array_sum(array_map(
                    static fn (TimelineDay $day): int => $day->github->toArray()[$key],
                    $days,
                )),
            );
        }

        return $types;
    }

    private function emptyGithubDay(): GithubDay
    {
        return new GithubDay(
            total: 0,
            prs: 0,
            reviews: 0,
            commits: 0,
            issues: 0,
            comments: 0,
            reviewComments: 0,
            people: 0,
        );
    }

    /**
     * @param  literal-string  $key
     * @return literal-string
     */
    private function column(string $key): string
    {
        return match ($key) {
            'reviewComments' => 'review_comments',
            default => $key,
        };
    }

    private function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function displayTimezone(): string
    {
        $timezone = config('app.display_timezone');

        return is_string($timezone) ? $timezone : 'UTC';
    }
}
