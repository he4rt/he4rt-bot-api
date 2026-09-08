<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Community\Retrospective\DTOs\SlideDescriptor;

/**
 * Um chip de slide na coluna de estrutura do Deck Builder: o descriptor que a
 * fonte publicou em slideCatalog() somado ao on/off que o deck_config guarda.
 *
 * O kind é a identidade: um kind pode render vários slides (github.repos = um
 * card por repositório) e o toggle esconde o bloco inteiro (ADR-0002).
 */
final readonly class SlideEntry
{
    public function __construct(
        public string $kind,
        public string $label,
        public ?string $hint,
        public bool $visible,
    ) {}

    public static function fromDescriptor(SlideDescriptor $descriptor, bool $visible): self
    {
        return new self(
            kind: $descriptor->kind,
            label: $descriptor->label,
            hint: $descriptor->hint,
            visible: $visible,
        );
    }
}
