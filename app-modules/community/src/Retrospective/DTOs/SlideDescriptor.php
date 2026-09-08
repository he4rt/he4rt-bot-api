<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

/**
 * Entrada do catálogo de slides de uma fonte: o que ela PODE emitir, descrito
 * sem coletar dado. O builder usa o kind como chave de on/off (o mesmo kind que
 * DeckConfig::hiddenSlides guarda) e o label/hint para dar nome à coisa na
 * timeline de estrutura.
 */
final readonly class SlideDescriptor
{
    public function __construct(
        public string $kind,
        public string $label,
        public ?string $hint = null,
    ) {}

    /**
     * @return array{kind: string, label: string, hint: string|null}
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'label' => $this->label,
            'hint' => $this->hint,
        ];
    }
}
