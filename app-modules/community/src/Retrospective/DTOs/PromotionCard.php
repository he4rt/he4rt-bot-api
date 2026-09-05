<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Enums\PromotionStage;

/**
 * Uma pessoa pronta para o deck: a escolha do operador (PromotionEntry) já
 * resolvida em quem ela é e no que ela fez no recorte, com as métricas separadas
 * por fonte.
 *
 * É o que o snapshot congela. Uma vez publicado, o cartão não volta ao banco:
 * quem assiste a retro de 2026 em 2030 vê os números de 2026, e a página pública
 * não paga consulta por pessoa a cada visita.
 */
final readonly class PromotionCard
{
    /**
     * @param  list<PromotionMetricGroup>  $groups
     */
    public function __construct(
        public string $userId,
        public string $name,
        public string $username,
        public string $avatar,
        public PromotionStage $stage,
        public ?string $reason = null,
        public array $groups = [],
        public ?CarbonImmutable $memberSince = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function makeFromPayload(array $payload): ?self
    {
        $userId = $payload['user_id'] ?? null;
        $stage = PromotionStage::tryFrom((string) ($payload['stage'] ?? ''));

        if (!is_string($userId) || !$stage instanceof PromotionStage) {
            return null;
        }

        $reason = $payload['reason'] ?? null;
        $memberSince = $payload['member_since'] ?? null;

        return new self(
            userId: $userId,
            name: (string) ($payload['name'] ?? ''),
            username: (string) ($payload['username'] ?? ''),
            avatar: (string) ($payload['avatar'] ?? ''),
            stage: $stage,
            reason: is_string($reason) && $reason !== '' ? $reason : null,
            groups: self::groups($payload['groups'] ?? []),
            memberSince: is_string($memberSince) && $memberSince !== ''
                ? CarbonImmutable::parse($memberSince)
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'stage' => $this->stage->value,
            'reason' => $this->reason,
            'member_since' => $this->memberSince?->toIso8601String(),
            'groups' => array_map(
                fn (PromotionMetricGroup $group): array => [
                    'source_key' => $group->sourceKey,
                    'source_label' => $group->sourceLabel,
                    'metrics' => array_map(
                        fn (Metric $metric): array => ['label' => $metric->label, 'value' => $metric->value],
                        $group->metrics,
                    ),
                ],
                $this->groups,
            ),
        ];
    }

    /**
     * A escolha que gerou este cartão, para o painel comparar o publicado com a
     * curadoria atual sem guardar as entries duas vezes.
     */
    public function signature(): string
    {
        return $this->userId.'|'.$this->stage->value.'|'.($this->reason ?? '');
    }

    /**
     * @return list<PromotionMetricGroup>
     */
    private static function groups(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $groups = [];

        foreach ($raw as $group) {
            if (!is_array($group)) {
                continue;
            }

            $metrics = [];

            foreach ((array) ($group['metrics'] ?? []) as $metric) {
                if (is_array($metric) && is_string($metric['label'] ?? null)) {
                    $metrics[] = new Metric($metric['label'], (int) ($metric['value'] ?? 0));
                }
            }

            $groups[] = new PromotionMetricGroup(
                sourceKey: (string) ($group['source_key'] ?? ''),
                sourceLabel: (string) ($group['source_label'] ?? ''),
                metrics: $metrics,
            );
        }

        return $groups;
    }
}
