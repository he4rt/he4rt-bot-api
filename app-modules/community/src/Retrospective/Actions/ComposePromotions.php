<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Actions;

use He4rt\Community\Retrospective\Contracts\MeasuresPerson;
use He4rt\Community\Retrospective\Contracts\PersonDirectory;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\PersonIdentity;
use He4rt\Community\Retrospective\DTOs\PromotionCard;
use He4rt\Community\Retrospective\DTOs\PromotionEntry;
use He4rt\Community\Retrospective\DTOs\PromotionMetricGroup;
use He4rt\Community\Retrospective\DTOs\SourceFilters;

/**
 * Monta os cartões do slide da tag He4rt: pega QUEM o operador escolheu e
 * pergunta a cada fonte o que ela sabe sobre aquela pessoa no recorte.
 *
 * É a orquestração de que o ADR-0001 fala — o único lugar onde números de
 * plataformas diferentes podem se encontrar. E mesmo aqui eles não se somam:
 * ficam empilhados por fonte, cada um com a marca de quem o produziu.
 *
 * Uma fonte que não implemente MeasuresPerson simplesmente não contribui; o
 * cartão sai com as faixas de quem sabia medir.
 */
final readonly class ComposePromotions
{
    /** @var list<RetrospectiveSource> */
    private array $sources;

    /**
     * @param  iterable<RetrospectiveSource>  $sources
     */
    public function __construct(
        iterable $sources,
        private PersonDirectory $people,
    ) {
        $this->sources = array_values(
            is_array($sources) ? $sources : iterator_to_array($sources, preserve_keys: false),
        );
    }

    /**
     * @param  list<PromotionEntry>  $entries
     * @return list<PromotionCard>
     */
    public function execute(array $entries, Period $period, SourceFilters $filters): array
    {
        if ($entries === []) {
            return [];
        }

        $people = $this->people->execute(
            array_map(static fn (PromotionEntry $entry): string => $entry->userId, $entries),
        );

        $cards = [];

        foreach ($entries as $entry) {
            $person = $people[$entry->userId] ?? null;

            // Usuário apagado depois de escolhido, ou sem conta em plataforma
            // nenhuma: sai da composição em silêncio em vez de virar um cartão sem
            // rosto e sem número. O painel avisa antes disso, na hora de escolher.
            if (!$person instanceof PersonIdentity || !$person->hasAccounts()) {
                continue;
            }

            $cards[] = new PromotionCard(
                userId: $person->userId,
                name: $person->name,
                username: $person->username,
                avatar: $person->avatar,
                stage: $entry->stage,
                reason: $entry->reason,
                groups: $this->groups($person, $period, $filters),
                memberSince: $person->memberSince,
            );
        }

        return $cards;
    }

    /**
     * @return list<PromotionMetricGroup>
     */
    private function groups(PersonIdentity $person, Period $period, SourceFilters $filters): array
    {
        $groups = [];

        foreach ($this->sources as $source) {
            if (!$source instanceof MeasuresPerson) {
                continue;
            }

            $group = new PromotionMetricGroup(
                sourceKey: $source->key(),
                sourceLabel: $source->label(),
                metrics: $source->measure($person, $period, $filters),
            );

            if (!$group->isEmpty()) {
                $groups[] = $group;
            }
        }

        return $groups;
    }
}
