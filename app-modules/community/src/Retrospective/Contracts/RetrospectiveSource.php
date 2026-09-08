<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Contracts;

use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;

/**
 * Estratégia de fonte da retrospectiva. Uma implementação por plataforma, no
 * módulo dono do dado (GithubSource em integration-github, DiscordSource em
 * activity). As fontes são descobertas por tagged services (tag
 * "retrospective.source"); adicionar uma plataforma = 1 classe + 1 tag, sem
 * tocar o portal.
 *
 * collect() agrega em SQL escopado pelo Period (nunca carrega linhas em PHP) e
 * aplica os SourceFilters (hideBots/exclusions) no WHERE, para o headline sair
 * consistente com os slides.
 */
interface RetrospectiveSource
{
    public function key(): string;

    /**
     * Nome de exibição da fonte (ex.: "GitHub", "Discord"). Estático, resolvível
     * sem coletar dado — o CRUD editorial lista/ordena as fontes por (key, label)
     * antes de qualquer collect().
     */
    public function label(): string;

    public function collect(Period $period, SourceFilters $filters): SourceResult;
}
