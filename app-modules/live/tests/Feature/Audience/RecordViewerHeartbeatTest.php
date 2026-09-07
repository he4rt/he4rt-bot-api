<?php

declare(strict_types=1);

use He4rt\Live\Audience\Actions\RecordViewerHeartbeat;
use He4rt\Live\Audience\InMemoryViewerPresence;
use He4rt\Live\Contracts\ViewerPresenceContract;
use He4rt\Live\Models\Live;

beforeEach(function (): void {
    $this->app->singleton(ViewerPresenceContract::class, InMemoryViewerPresence::class);
});

it('conta espectadores distintos na janela ativa', function (): void {
    $live = Live::factory()->onAir()->create();
    $action = resolve(RecordViewerHeartbeat::class);

    $action->execute($live, 'sessao-a');
    $action->execute($live, 'sessao-a');

    $viewers = $action->execute($live, 'sessao-b');

    expect($viewers)->toBe(2);
});

it('atualiza o pico apenas quando superado', function (): void {
    $live = Live::factory()->onAir()->create(['peak_viewers' => 5]);
    $action = resolve(RecordViewerHeartbeat::class);

    $action->execute($live, 'sessao-a');

    expect($live->refresh()->peak_viewers)->toBe(5);

    foreach (range(1, 6) as $i) {
        $action->execute($live, "sessao-{$i}");
    }

    expect($live->refresh()->peak_viewers)->toBe(7);
});

it('expira presenças fora da janela de 30 segundos', function (): void {
    $live = Live::factory()->onAir()->create();
    $action = resolve(RecordViewerHeartbeat::class);

    $action->execute($live, 'sessao-antiga');
    $this->travel(31)->seconds();

    expect($action->execute($live, 'sessao-nova'))->toBe(1);
});
