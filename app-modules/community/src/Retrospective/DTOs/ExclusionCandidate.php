<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

use He4rt\Community\Retrospective\Enums\ExclusionKind;

/**
 * Algo que o operador pode esconder do deck, oferecido pela própria fonte para o
 * picker do Deck Builder. O ref é a chave que volta em SourceFilters::exclusions
 * e que a fonte reconhece no collect() — namespaced por prefixo ("pr:142",
 * "actor:maria", "message:<uuid>", "member:<uuid>") para duas fontes nunca
 * disputarem o mesmo ref na lista achatada do DeckConfig.
 *
 * Exclusion mexe no DADO (ADR-0001): sai do slide e também dos números, então
 * alterá-la exige republicar para recompilar o snapshot.
 */
final readonly class ExclusionCandidate
{
    public function __construct(
        public string $ref,
        public string $label,
        public ExclusionKind $kind,
        public ?string $hint = null,
    ) {}

    public static function item(string $ref, string $label, ?string $hint = null): self
    {
        return new self($ref, $label, ExclusionKind::Item, $hint);
    }

    public static function person(string $ref, string $label, ?string $hint = null): self
    {
        return new self($ref, $label, ExclusionKind::Person, $hint);
    }

    /**
     * @return array{ref: string, label: string, kind: string, hint: string|null}
     */
    public function toArray(): array
    {
        return [
            'ref' => $this->ref,
            'label' => $this->label,
            'kind' => $this->kind->value,
            'hint' => $this->hint,
        ];
    }
}
