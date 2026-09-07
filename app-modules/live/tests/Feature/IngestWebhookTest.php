<?php

declare(strict_types=1);

use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Events\LiveStarted;
use He4rt\Live\Models\Live;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    config()->set('live.webhook_secret', 'segredo-do-teste');
});

function webhook(array $body, ?string $secret = 'segredo-do-teste'): TestResponse
{
    $headers = $secret === null ? [] : ['X-Live-Webhook-Secret' => $secret];

    return postJson('/live/ingest/webhook', $body, $headers);
}

it('recusa webhook sem secret válido', function (): void {
    webhook(['event' => 'online', 'path' => 'live'], secret: 'errado')->assertForbidden();
    webhook(['event' => 'online', 'path' => 'live'], secret: null)->assertForbidden();
});

it('marca a live corrente como no ar quando o sinal fica online', function (): void {
    Event::fake([LiveStarted::class]);
    $live = Live::factory()->create();

    webhook(['event' => 'online', 'path' => 'live'])->assertNoContent();

    expect($live->refresh()->status)->toBe(LiveStatus::OnAir)
        ->and($live->started_at)->not->toBeNull();
    Event::assertDispatched(LiveStarted::class);
});

it('ignora online sem live corrente', function (): void {
    Event::fake([LiveStarted::class]);
    Live::factory()->ended()->create();

    webhook(['event' => 'online', 'path' => 'live'])->assertNoContent();

    Event::assertNotDispatched(LiveStarted::class);
});

it('aceita e ignora o evento offline', function (): void {
    $live = Live::factory()->onAir()->create();

    webhook(['event' => 'offline', 'path' => 'live'])->assertNoContent();

    expect($live->refresh()->status)->toBe(LiveStatus::OnAir);
});
