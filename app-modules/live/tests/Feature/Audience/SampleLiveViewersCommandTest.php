<?php

declare(strict_types=1);

use He4rt\Live\Audience\InMemoryViewerPresence;
use He4rt\Live\Contracts\ViewerPresenceContract;
use He4rt\Live\Models\Live;
use He4rt\Live\Models\LiveViewerSample;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    $this->app->singleton(ViewerPresenceContract::class, InMemoryViewerPresence::class);
});

it('grava uma amostra para a live no ar', function (): void {
    $live = Live::factory()->onAir()->create();
    resolve(ViewerPresenceContract::class)->touch($live->id, 'sessao-a');

    artisan('live:sample-viewers')->assertSuccessful();

    $sample = LiveViewerSample::query()->sole();
    expect($sample->live_id)->toBe($live->id)->and($sample->viewers)->toBe(1);
});

it('não grava amostra sem live no ar', function (): void {
    Live::factory()->create();

    artisan('live:sample-viewers')->assertSuccessful();

    expect(LiveViewerSample::query()->count())->toBe(0);
});
