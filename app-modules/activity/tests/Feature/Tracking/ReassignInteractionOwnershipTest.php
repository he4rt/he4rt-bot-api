<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\Auth\Events\AccountsMerged;
use He4rt\Identity\User\Models\User;

test('interações seguem o usuário sobrevivente no merge', function (): void {
    $merged = User::factory()->create();
    $survivor = User::factory()->create();

    $interactions = Interaction::factory()->count(3)->create(['user_id' => $merged->id]);
    $identities = $interactions->pluck('external_identity_id');

    event(new AccountsMerged($survivor->id, $merged->id));

    expect(Interaction::query()->where('user_id', $survivor->id)->count())->toBe(3)
        ->and(Interaction::query()->where('user_id', $merged->id)->count())->toBe(0)
        ->and(Interaction::query()->pluck('external_identity_id')->sort()->values()->all())
        ->toBe($identities->sort()->values()->all());
});
