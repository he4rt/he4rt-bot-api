<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

/**
 * Filtros que mexem no DADO (camada 1 de curadoria), aplicados dentro do
 * collect() de cada fonte para o headline sair consistente. Curadoria de
 * apresentação (ordem, on/off, títulos) NÃO entra aqui: mora na orquestração.
 */
final readonly class SourceFilters
{
    /**
     * @param  list<string>  $exclusions  refs escondidos deck-wide, ex.: "pr:142", "actor:login"
     */
    public function __construct(
        public bool $hideBots = true,
        public array $exclusions = [],
    ) {}

    public function excludes(string $ref): bool
    {
        return in_array($ref, $this->exclusions, strict: true);
    }

    /**
     * Refs de um prefixo, já sem ele (prefixo "member:" devolve os ids). Serve à
     * fonte que filtra em SQL: a lista chega achatada de todas as fontes, e cada
     * uma reconhece só os prefixos que emite.
     *
     * @return list<string>
     */
    public function refsWithPrefix(string $prefix): array
    {
        $refs = [];

        foreach ($this->exclusions as $ref) {
            if (str_starts_with($ref, $prefix)) {
                $refs[] = mb_substr($ref, mb_strlen($prefix));
            }
        }

        return $refs;
    }
}
