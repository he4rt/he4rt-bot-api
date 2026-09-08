<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Actions\HideInteraction;
use He4rt\Activity\Tracking\Actions\UnhideInteraction;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\User\Models\User;

test('contribuição nasce visível', function (): void {
    $interaction = Interaction::factory()->create();

    expect($interaction->hidden_at)->toBeNull()
        ->and($interaction->isVisible())->toBeTrue();
});

test('ocultar registra quem e quando', function (): void {
    $interaction = Interaction::factory()->create();
    $moderator = User::factory()->create();

    $hidden = resolve(HideInteraction::class)->handle($interaction, $moderator);

    expect($hidden->hidden_at)->not->toBeNull()
        ->and($hidden->hidden_by)->toBe($moderator->id)
        ->and($hidden->isVisible())->toBeFalse();
});

test('mostrar limpa a ocultação', function (): void {
    $interaction = Interaction::factory()->hidden()->create([
        'hidden_by' => User::factory(),
    ]);

    $visible = resolve(UnhideInteraction::class)->handle($interaction);

    expect($visible->hidden_at)->toBeNull()
        ->and($visible->hidden_by)->toBeNull();
});

test('os escopos separam visíveis de ocultas', function (): void {
    Interaction::factory()->count(2)->create();
    Interaction::factory()->hidden()->create();

    expect(Interaction::query()->visible()->count())->toBe(2)
        ->and(Interaction::query()->hidden()->count())->toBe(1);
});
