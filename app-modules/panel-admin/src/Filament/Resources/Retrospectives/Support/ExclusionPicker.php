<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Community\Retrospective\Contracts\CuratableSource;
use He4rt\Community\Retrospective\DTOs\ExclusionCandidate;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\Enums\ExclusionKind;

/**
 * Traduz o que a fonte oferece em exclusionCandidates() para as opções do picker,
 * agrupadas por ExclusionKind (item e pessoa são tipos distintos, não uma escala).
 *
 * A varredura tem teto por contrato (30 no GitHub, 20 no Discord), então o picker
 * mostra o topo do recorte — nunca a tabela inteira. Daí `orphans()`: um ref já
 * excluído que caiu fora do teto não aparece em opção nenhuma, e a UI não pode
 * derrubar aquilo que não consegue exibir.
 */
final readonly class ExclusionPicker
{
    /**
     * @param  array<string, list<ExclusionCandidate>>  $byKind
     */
    private function __construct(private array $byKind) {}

    public static function for(CuratableSource $source, Period $period): self
    {
        $byKind = array_fill_keys(
            array_map(static fn (ExclusionKind $kind): string => $kind->value, ExclusionKind::cases()),
            [],
        );

        foreach ($source->exclusionCandidates($period) as $candidate) {
            $byKind[$candidate->kind->value][] = $candidate;
        }

        return new self($byKind);
    }

    /**
     * @return array<string, string> ref => label
     */
    public function options(ExclusionKind $kind): array
    {
        $options = [];

        foreach ($this->byKind[$kind->value] ?? [] as $candidate) {
            $options[$candidate->ref] = $candidate->label;
        }

        return $options;
    }

    /**
     * @return array<string, string> ref => hint
     */
    public function descriptions(ExclusionKind $kind): array
    {
        $descriptions = [];

        foreach ($this->byKind[$kind->value] ?? [] as $candidate) {
            if ($candidate->hint !== null) {
                $descriptions[$candidate->ref] = $candidate->hint;
            }
        }

        return $descriptions;
    }

    public function hasOptions(ExclusionKind $kind): bool
    {
        return ($this->byKind[$kind->value] ?? []) !== [];
    }

    /**
     * Os refs já excluídos que este picker consegue exibir, prontos para virar
     * estado marcado do CheckboxList daquele kind.
     *
     * @param  list<string>  $excluded
     * @return list<string>
     */
    public function selected(ExclusionKind $kind, array $excluded): array
    {
        return array_values(array_intersect($excluded, array_keys($this->options($kind))));
    }

    /**
     * Refs excluídos que nenhum kind oferece — fora do teto da varredura ou de um
     * recorte anterior. Precisam ser reescritos no salvamento, senão desmarcar
     * qualquer coisa no picker os apagaria por omissão.
     *
     * @param  list<string>  $excluded
     * @return list<string>
     */
    public function orphans(array $excluded): array
    {
        return array_values(array_diff($excluded, $this->offeredRefs()));
    }

    /**
     * @return list<string>
     */
    private function offeredRefs(): array
    {
        $refs = [];

        foreach ($this->byKind as $candidates) {
            foreach ($candidates as $candidate) {
                $refs[] = $candidate->ref;
            }
        }

        return $refs;
    }
}
