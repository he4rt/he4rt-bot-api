<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\IntegrationDiscord\Retrospective\DiscordMembershipDates;

it('responde o joined_at mais antigo entre os servidores da conta', function (): void {
    $identity = ExternalIdentity::factory()->create();

    DiscordMember::factory()->create([
        'external_identity_id' => $identity->id,
        'joined_at' => '2022-06-01 10:00:00',
    ]);
    DiscordMember::factory()->create([
        'external_identity_id' => $identity->id,
        'joined_at' => '2019-02-14 08:30:00',
    ]);

    $dates = new DiscordMembershipDates()->execute([$identity->id]);

    expect($dates[$identity->id]->toDateString())->toBe('2019-02-14');
});

it('ignora membros sem joined_at e contas fora da lista', function (): void {
    $comData = ExternalIdentity::factory()->create();
    $semData = ExternalIdentity::factory()->create();
    $foraDaLista = ExternalIdentity::factory()->create();

    DiscordMember::factory()->create([
        'external_identity_id' => $comData->id,
        'joined_at' => '2021-01-01 00:00:00',
    ]);
    DiscordMember::factory()->create([
        'external_identity_id' => $semData->id,
        'joined_at' => null,
    ]);
    DiscordMember::factory()->create([
        'external_identity_id' => $foraDaLista->id,
        'joined_at' => '2018-01-01 00:00:00',
    ]);

    $dates = new DiscordMembershipDates()->execute([$comData->id, $semData->id]);

    expect($dates)->toHaveKeys([$comData->id])
        ->and($dates)->not->toHaveKeys([$semData->id, $foraDaLista->id]);
});

it('lista vazia não vai ao banco', function (): void {
    expect(new DiscordMembershipDates()->execute([]))->toBeEmpty();
});
