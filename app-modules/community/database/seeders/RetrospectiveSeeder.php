<?php

declare(strict_types=1);

namespace He4rt\Community\Database\Seeders;

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Models\Retrospective;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Monta uma edição de retrospectiva para cada estado que o Deck Builder precisa
 * saber renderizar: rascunho cru, rascunho já curado, publicada, publicada com
 * exclusion alterada (o aviso de republicar), publicando e recorte vazio.
 *
 * Não semeia dado de fonte: quem faz isso são os seeders dos módulos que possuem
 * o dado (integration-github, activity). Este módulo é de domínio e não conhece
 * nenhuma implementação de fonte — só o contrato e a tag do container.
 */
final class RetrospectiveSeeder extends Seeder
{
    /** Rascunho rico e sem curadoria: o ponto de partida para testar o builder. */
    public const string RICH_DRAFT = 'Seis meses de He4rt';

    /** Rascunho com curadoria já aplicada: ordem, slide oculto e exclusions. */
    public const string CURATED_DRAFT = 'Um mês de comunidade';

    /** Publicada de verdade, com snapshot congelado pelo pipeline real. */
    public const string PUBLISHED = 'O primeiro ano da retrospectiva';

    /** Publicada e depois curada no dado: needsRepublish() fica true. */
    public const string DRIFTED = 'Segundo semestre, revisitado';

    /** Estado transitório: o badge de status e o poll de 3s do builder. */
    public const string PUBLISHING = 'Trimestre em compilação';

    /** Recorte sem dado nenhum: os estados vazios do builder e do preview. */
    public const string ARCHIVED = 'Arquivo: o recorte vazio';

    /**
     * @param  array<string, list<string>>  $baits  refs plantados por fonte, para o
     *                                              picker de exclusions ter o que oferecer
     */
    public function run(array $baits = []): void
    {
        $anchor = CarbonImmutable::now()->startOfMonth();
        $order = $this->sourceKeys();

        $this->richDraft($anchor, $order);
        $this->curatedDraft($anchor, $order, $baits);
        $this->published($anchor, $order);
        $this->drifted($anchor, $order, $baits);
        $this->publishing($anchor, $order);
        $this->archived($order);
    }

    /**
     * @param  list<string>  $order
     */
    private function richDraft(CarbonImmutable $anchor, array $order): void
    {
        Retrospective::factory()->create([
            'title' => self::RICH_DRAFT,
            'since' => $anchor->subMonths(6),
            'until' => $anchor->subSecond(),
            'status' => RetrospectiveStatus::Draft,
            'cover_title' => 'Seis meses de He4rt',
            'cover_intro' => 'Do primeiro commit do semestre à última call de madrugada: o que a comunidade construiu entre fevereiro e julho.',
            'closing_text' => 'Nada disso aparece sozinho. Obrigado a quem abriu PR, revisou, respondeu dúvida às duas da manhã e apareceu na call.',
            'hide_bots' => true,
            'deck_config' => new DeckConfig(order: $order),
        ]);
    }

    /**
     * Curadoria já aplicada, para dar pra comparar efeito com o rascunho cru sem
     * ter de mexer em nada: ordem invertida em relação à descoberta, cards de
     * repositório escondidos, bots à mostra e exclusions nas duas fontes.
     *
     * @param  list<string>  $order
     * @param  array<string, list<string>>  $baits
     */
    private function curatedDraft(CarbonImmutable $anchor, array $order, array $baits): void
    {
        $config = new DeckConfig(
            order: array_reverse($order),
            hiddenSlides: ['github.repos', 'discord.new_members'],
            exclusions: $baits,
        );

        Retrospective::factory()->create([
            'title' => self::CURATED_DRAFT,
            'since' => $anchor->subMonth(),
            'until' => $anchor->subSecond(),
            'status' => RetrospectiveStatus::Draft,
            'cover_title' => 'Julho na He4rt',
            'cover_intro' => 'Um mês só, com a ordem editorial invertida e os cards de repositório fora do deck.',
            'closing_text' => 'Agosto já começou. Aparece lá.',
            // Bots à mostra de propósito: é o contraste com o rascunho rico, que
            // esconde. O mesmo recorte com dois números diferentes.
            'hide_bots' => false,
            'deck_config' => $config,
        ]);
    }

    /**
     * @param  list<string>  $order
     */
    private function published(CarbonImmutable $anchor, array $order): void
    {
        $retrospective = Retrospective::factory()->create([
            'title' => self::PUBLISHED,
            'since' => $anchor->subMonths(13),
            'until' => $anchor->subMonths(7)->subSecond(),
            'status' => RetrospectiveStatus::Draft,
            'cover_title' => 'O primeiro ano da retrospectiva',
            'cover_intro' => 'Seis meses de comunidade congelados no dia da publicação — é este número que a página pública mostra, não o de hoje.',
            'closing_text' => 'Feito por quem apareceu. 💜',
            'hide_bots' => true,
            'deck_config' => new DeckConfig(order: $order, hiddenSlides: ['github.community']),
        ]);

        $this->freeze($retrospective);
    }

    /**
     * Publica primeiro e só depois adiciona exclusion: exclusion mexe no DADO, não
     * na apresentação, então o snapshot congelado passa a discordar da curadoria
     * atual e o builder precisa avisar "republique" (needsRepublish()).
     *
     * @param  list<string>  $order
     * @param  array<string, list<string>>  $baits
     */
    private function drifted(CarbonImmutable $anchor, array $order, array $baits): void
    {
        $retrospective = Retrospective::factory()->create([
            'title' => self::DRIFTED,
            'since' => $anchor->subMonths(13),
            'until' => $anchor->subMonths(8)->subSecond(),
            'status' => RetrospectiveStatus::Draft,
            'cover_title' => 'Segundo semestre, revisitado',
            'cover_intro' => 'Publicada antes da curadoria de dado — os números aqui são os de antes da exclusion.',
            'closing_text' => 'Republique para os números baterem com a curadoria atual.',
            'hide_bots' => true,
            'deck_config' => new DeckConfig(order: $order),
        ]);

        $this->freeze($retrospective);

        $retrospective->forceFill([
            'deck_config' => new DeckConfig(order: $order, exclusions: $baits),
        ])->save();
    }

    /**
     * @param  list<string>  $order
     */
    private function publishing(CarbonImmutable $anchor, array $order): void
    {
        Retrospective::factory()->create([
            'title' => self::PUBLISHING,
            'since' => $anchor->subMonths(7),
            'until' => $anchor->subMonths(4)->subSecond(),
            'status' => RetrospectiveStatus::Publishing,
            'cover_title' => 'Trimestre em compilação',
            'cover_intro' => 'Esta edição ficou travada em "publicando" de propósito: é o estado que o builder acompanha com poll de 3 segundos.',
            'closing_text' => 'Clique em publicar para o job rodar de verdade.',
            'hide_bots' => true,
            'deck_config' => new DeckConfig(order: $order),
        ]);
    }

    /**
     * @param  list<string>  $order
     */
    private function archived(array $order): void
    {
        Retrospective::factory()->create([
            'title' => self::ARCHIVED,
            'since' => CarbonImmutable::parse('2019-01-01 00:00:00'),
            'until' => CarbonImmutable::parse('2019-12-31 23:59:59'),
            'status' => RetrospectiveStatus::Draft,
            'cover_title' => 'Arquivo de 2019',
            'cover_intro' => 'Recorte sem dado nenhum, para ver como o builder e o preview se comportam vazios.',
            'closing_text' => null,
            'hide_bots' => true,
            'deck_config' => new DeckConfig(order: $order),
        ]);
    }

    /**
     * Congela o snapshot pelo mesmo caminho do job de publicação — nada de
     * snapshot fabricado à mão, senão o playground mentiria sobre o formato.
     */
    private function freeze(Retrospective $retrospective): void
    {
        $snapshot = resolve(CompileSnapshot::class)->execute(
            $retrospective->period(),
            $retrospective->filters(),
        );

        $retrospective->forceFill([
            'snapshot' => $snapshot,
            'status' => RetrospectiveStatus::Published,
            'published_at' => Date::now(),
        ])->save();
    }

    /**
     * Ordem editorial inicial = ordem de descoberta das fontes. Depende só da tag
     * do container, nunca de conhecer integration-github ou activity.
     *
     * @return list<string>
     */
    private function sourceKeys(): array
    {
        $keys = [];

        /** @var iterable<RetrospectiveSource> $sources */
        $sources = app()->tagged('retrospective.source');

        foreach ($sources as $source) {
            $keys[] = $source->key();
        }

        return $keys;
    }
}
