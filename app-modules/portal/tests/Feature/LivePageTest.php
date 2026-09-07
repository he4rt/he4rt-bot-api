<?php

declare(strict_types=1);

use He4rt\Live\Audience\InMemoryViewerPresence;
use He4rt\Live\Contracts\ViewerPresenceContract;
use He4rt\Live\Models\Live;
use He4rt\Portal\Live\LivePage;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();
    $this->app->singleton(ViewerPresenceContract::class, InMemoryViewerPresence::class);
});

it('mostra o estado offline quando não há live corrente', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => false, 'readyTime' => null])]);

    get('/live')
        ->assertOk()
        ->assertSee('Nenhuma live no ar agora')
        ->assertDontSee('data-live-player', escape: false);
});

it('mostra título, descrição e chat quando há live corrente', function (): void {
    Live::factory()->onAir()->create(['title' => 'Retrô de agosto', 'description' => 'Balanço do mês']);
    Http::fake(['localhost:9997/*' => Http::response(['ready' => true, 'readyTime' => '2026-08-29T20:00:00Z'])]);

    get('/live')
        ->assertOk()
        ->assertSee('Retrô de agosto')
        ->assertSee('Balanço do mês')
        ->assertSee('data-live-player', escape: false)
        ->assertSee('data-live-channel', escape: false);
});

it('mostra aguardando sinal quando a live existe mas o stream caiu', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => false, 'readyTime' => null])]);
    Live::factory()->onAir()->create();

    get('/live')
        ->assertOk()
        ->assertSee('Aguardando sinal');
});

it('registra heartbeat e expõe o contador no poll', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => false, 'readyTime' => null])]);
    $live = Live::factory()->onAir()->create();

    Livewire::test(LivePage::class)
        ->call('pulse')
        ->assertSet('viewers', 1);

    expect(resolve(ViewerPresenceContract::class)->countActive($live->id))->toBe(1);
});

it('mantém o último valor de viewers quando o presence falha (ex.: redis fora do ar)', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => false, 'readyTime' => null])]);
    Live::factory()->onAir()->create();

    $this->app->singleton(ViewerPresenceContract::class, fn (): ViewerPresenceContract => new class implements ViewerPresenceContract
    {
        public function touch(string $liveId, string $visitorId): void
        {
            throw new RuntimeException('redis indisponível');
        }

        public function countActive(string $liveId): int
        {
            return 0;
        }
    });

    Livewire::test(LivePage::class)
        ->call('pulse')
        ->assertSet('viewers', 0);
});

it('carrega o entry do player pelo vite dentro do main', function (): void {
    Live::factory()->onAir()->create();
    Http::fake(['localhost:9997/*' => Http::response(['ready' => true, 'readyTime' => '2026-08-29T20:00:00Z'])]);
    $this->withVite();

    $html = get('/live')->assertOk()->getContent();

    expect(mb_substr_count($html, '<main'))->toBe(1)
        ->and(mb_strpos($html, 'live-player'))->toBeGreaterThan(mb_strpos($html, '<main'));
});
