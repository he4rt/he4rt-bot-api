<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use LogicException;

/**
 * O que está selecionado na coluna de estrutura, como valor tipado em vez de
 * string solta com prefixo espalhada pela Page.
 *
 * O token viaja pela wire (`selection`), então `parse()` é a fronteira: qualquer
 * coisa que não seja um modo conhecido degrada para a capa em vez de explodir.
 */
final readonly class InspectorSelection
{
    public function __construct(
        public InspectorMode $mode,
        public ?string $target = null,
    ) {}

    public static function cover(): self
    {
        return new self(InspectorMode::Cover);
    }

    public static function parse(string $token): self
    {
        [$prefix, $target] = array_pad(explode(':', $token, limit: 2), 2, value: null);

        $mode = InspectorMode::tryFrom($prefix);

        return match (true) {
            $mode === InspectorMode::Cover, $mode === InspectorMode::Closing => new self($mode),
            // Bloco, slide, seção fixa e promoção são inúteis sem alvo: sem ele,
            // cai para a capa.
            (in_array($mode, [InspectorMode::About, InspectorMode::Source, InspectorMode::Slide, InspectorMode::Promotion], strict: true)) && $target !== null && $target !== '' => new self($mode, $target),
            default => self::cover(),
        };
    }

    public function token(): string
    {
        return $this->target === null
            ? $this->mode->value
            : $this->mode->value.':'.$this->target;
    }

    /**
     * O alvo dos modos que exigem um. `parse()` garante a presença; o throw
     * documenta o invariante para quem construir o DTO à mão.
     */
    public function requireTarget(): string
    {
        if ($this->target === null) {
            throw new LogicException(sprintf('O modo %s do inspector exige um alvo.', $this->mode->value));
        }

        return $this->target;
    }

    public function is(InspectorMode $mode): bool
    {
        return $this->mode === $mode;
    }

    public function selects(InspectorMode $mode, string $target): bool
    {
        return $this->mode === $mode && $this->target === $target;
    }
}
