<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Community\Retrospective\Contracts\CuratableSource;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\SlideDescriptor;

/**
 * Timeline da coluna de estrutura: as fontes registradas, na ordem editorial do
 * deck_config, cada uma com seu on/off e — se implementar CuratableSource — os
 * chips do seu catálogo de slides.
 *
 * Resolvida sem tocar o banco: só label() e slideCatalog(), ambos estáticos por
 * contrato. Ordena pelo mesmo position() que o ComposeDeck usa, para a estrutura
 * exibida bater com a ordem do deck renderizado.
 */
final class DeckStructure
{
    /**
     * @return list<SourceBlock>
     */
    public static function blocks(DeckConfig $config): array
    {
        $sources = array_values(AvailableSources::all());

        // Fontes fora de `order` empatam em position(); o sort estável do PHP
        // mantém a ordem de descoberta entre elas, igual ao ComposeDeck.
        usort(
            $sources,
            static fn (RetrospectiveSource $a, RetrospectiveSource $b): int => $config->position($a->key()) <=> $config->position($b->key()),
        );

        return array_map(
            static fn (RetrospectiveSource $source): SourceBlock => new SourceBlock(
                key: $source->key(),
                label: $source->label(),
                visible: $config->showsSource($source->key()),
                curatable: $source instanceof CuratableSource,
                slides: $source instanceof CuratableSource ? self::slides($source, $config) : [],
            ),
            $sources,
        );
    }

    /**
     * Nova ordem editorial com uma fonte deslocada `offset` posições. Devolve a
     * lista INTEIRA (não só o par trocado): é isso que ancora as fontes que ainda
     * não estavam em `order` — senão elas voltariam para o fim a cada movimento.
     *
     * @param  list<SourceBlock>  $blocks
     * @return list<string>
     */
    public static function moved(array $blocks, string $key, int $offset): array
    {
        $order = array_map(static fn (SourceBlock $block): string => $block->key, $blocks);

        $from = array_search($key, $order, strict: true);

        if ($from === false) {
            return $order;
        }

        $to = $from + $offset;

        if ($to < 0 || $to >= count($order)) {
            return $order;
        }

        [$order[$from], $order[$to]] = [$order[$to], $order[$from]];

        return array_values($order);
    }

    /**
     * @return list<SlideEntry>
     */
    private static function slides(CuratableSource $source, DeckConfig $config): array
    {
        return array_map(
            static fn (SlideDescriptor $descriptor): SlideEntry => SlideEntry::fromDescriptor(
                $descriptor,
                visible: $config->showsSlide($descriptor->kind),
            ),
            $source->slideCatalog(),
        );
    }
}
