<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Retrospective;

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Contracts\MembershipDates;
use He4rt\IntegrationDiscord\Models\DiscordMember;

/**
 * A resposta do Discord para "desde quando essa pessoa está aqui": o menor
 * `joined_at` entre os servidores onde a conta aparece.
 *
 * O mínimo importa porque sair e voltar reseta o `joined_at` de um servidor —
 * a guilda mais antiga é a memória que sobrou da chegada de verdade.
 */
final readonly class DiscordMembershipDates implements MembershipDates
{
    public function execute(array $identityIds): array
    {
        if ($identityIds === []) {
            return [];
        }

        $rows = DiscordMember::query()
            ->whereIn('external_identity_id', $identityIds)
            ->whereNotNull('joined_at')
            ->groupBy('external_identity_id')
            ->selectRaw('external_identity_id, min(joined_at) as first_joined_at')
            ->toBase()
            ->get();

        $dates = [];

        foreach ($rows as $row) {
            $identityId = $row->external_identity_id;
            $joinedAt = $row->first_joined_at;

            if (is_string($identityId) && is_string($joinedAt)) {
                $dates[$identityId] = CarbonImmutable::parse($joinedAt);
            }
        }

        return $dates;
    }
}
