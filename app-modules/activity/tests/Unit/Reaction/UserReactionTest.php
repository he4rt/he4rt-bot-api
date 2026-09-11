<?php

declare(strict_types=1);

use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use He4rt\Activity\Timeline\Timeline;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('cria uma linha válida via factory com reaction como enum', function (): void {
    UserReaction::factory()->create();

    $reaction = UserReaction::query()->first();

    expect($reaction)->not->toBeNull()
        ->and($reaction->reaction)->toBeInstanceOf(TimelineReaction::class);

    $this->assertDatabaseCount('activity_user_reactions', 1);
});

it('devolve as reações do post pela relação userReactions', function (): void {
    $timeline = Timeline::factory()->create();

    UserReaction::factory()
        ->count(3)
        ->for($timeline)
        ->create();

    expect($timeline->userReactions)->toHaveCount(3);
});
