<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Portal\Retrospective\SlideView;
use Illuminate\Support\Facades\View;

/**
 * A tira de miniaturas do Deck Builder: o catálogo de fontes (quem PODE aparecer)
 * casado com o snapshot cru (o que TEM dado para render).
 *
 * O catálogo é a espinha, de propósito. A composição some com o que está
 * desligado — e uma fonte que some da tira leva junto o botão que a religa. Aqui
 * on/off é ESTILO, não filtro: o slide oculto continua na tira, apagado.
 *
 * O snapshot precisa vir CRU (DeckPresentation::snapshotFor), não composto.
 */
final class DeckFilmstrip
{
    /**
     * @param  list<array{kind: string, source: string}>  $composedKinds  a forma do deck
     *                                                                    renderizado, para casar cada miniatura com o slide que ela representa
     * @param  int  $offset  quantos slides o deck desenha ANTES do primeiro composto
     *                       (capa + seção fixa). Vem de fora porque quem conta isso é a Page,
     *                       que já é dona do deslocamento — recontar aqui foi como a tira
     *                       ficou três posições adiantada quando a seção "A He4rt" entrou.
     * @return list<FilmstripGroup>
     */
    public static function groups(RetrospectiveSnapshot $snapshot, DeckConfig $config, array $composedKinds = [], int $offset = 1): array
    {
        $slidesBySource = [];

        foreach ($snapshot->sources as $source) {
            $slidesBySource[$source->key] = $source->slides;
        }

        $indices = self::indices($composedKinds, $offset);

        return array_map(
            static fn (SourceBlock $block): FilmstripGroup => new FilmstripGroup(
                key: $block->key,
                label: $block->label,
                visible: $block->visible,
                curatable: $block->curatable,
                slides: self::slides($slidesBySource[$block->key] ?? [], $block, $config, $indices),
            ),
            DeckStructure::blocks($config),
        );
    }

    /**
     * Fila de posições por (fonte, kind). É fila, não valor: um kind pode emitir
     * várias instâncias, e cada miniatura precisa da SUA posição no deck.
     *
     * Casar contra o composedKinds — e não recontar a ordem aqui — é o que mantém
     * um dono só dos índices. Uma fonte que exista no snapshot congelado mas já não
     * esteja registrada renderiza no deck e não tem bloco no catálogo; recontar
     * deslocaria todas as miniaturas seguintes.
     *
     * @param  list<array{kind: string, source: string}>  $composedKinds
     * @return array<string, list<int>>
     */
    private static function indices(array $composedKinds, int $offset): array
    {
        $indices = [];

        foreach ($composedKinds as $position => $entry) {
            $indices[$entry['source'].'|'.$entry['kind']][] = $position + $offset;
        }

        return $indices;
    }

    /**
     * Recebe as filas por VALOR: as chaves são namespaced por fonte, então um
     * bloco só consome as suas e nenhum outro enxerga o consumo.
     *
     * @param  list<Slide>  $slides
     * @param  array<string, list<int>>  $indices
     * @return list<FilmstripSlide>
     */
    private static function slides(array $slides, SourceBlock $block, DeckConfig $config, array $indices): array
    {
        $labels = self::labels($block);
        $strip = [];
        $rendered = [];

        foreach ($slides as $slide) {
            $kind = $slide->kind();
            $view = SlideView::kind($kind);

            // Kind sem partial: snapshot congelado antes de a view existir, ou
            // renomeada depois. Sem view não há miniatura — e uma caixa preta na
            // tira mentiria mais do que a ausência.
            if (!View::exists($view)) {
                continue;
            }

            // Oculto não entra na composição, então não tem posição no deck. Fila
            // vazia devolve null pelo mesmo caminho: a miniatura existe, mas não
            // leva a lugar nenhum.
            $queue = $block->key.'|'.$kind;
            // isset() e não blank()/empty(): a chave costuma NÃO existir (todo kind
            // oculto cai aqui), e blank() avalia o índice antes de testá-lo, o que
            // dispara "Undefined array key" a cada slide desligado.
            $index = isset($indices[$queue]) && $indices[$queue] !== []
                ? array_shift($indices[$queue])
                : null;

            $rendered[$kind] = true;

            $strip[] = new FilmstripSlide(
                kind: $kind,
                label: $labels[$kind] ?? $kind,
                visible: $config->showsSlide($kind),
                view: $view,
                props: $slide->toArray(),
                index: $index,
            );
        }

        return [...$strip, ...self::withoutData($block, $config, $rendered)];
    }

    /**
     * Os kinds que a fonte anuncia no catálogo mas que não renderam nada neste
     * recorte. Entram na tira sem miniatura — não há o que desenhar —, mas entram:
     * o rótulo é o que diz ao operador que o slide EXISTE e está vazio, e o
     * cartão é o que dá acesso ao on/off do kind. Some da tira só o que a fonte
     * nunca anunciou.
     *
     * @param  array<string, true>  $rendered
     * @return list<FilmstripSlide>
     */
    private static function withoutData(SourceBlock $block, DeckConfig $config, array $rendered): array
    {
        $empty = [];

        foreach ($block->slides as $entry) {
            if (isset($rendered[$entry->kind])) {
                continue;
            }

            $empty[] = new FilmstripSlide(
                kind: $entry->kind,
                label: $entry->label,
                visible: $config->showsSlide($entry->kind),
                view: null,
                props: [],
            );
        }

        return $empty;
    }

    /**
     * @return array<string, string> kind => rótulo do catálogo
     */
    private static function labels(SourceBlock $block): array
    {
        $labels = [];

        foreach ($block->slides as $entry) {
            $labels[$entry->kind] = $entry->label;
        }

        return $labels;
    }
}
