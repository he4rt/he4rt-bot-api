<?php

declare(strict_types=1);

use He4rt\Live\Models\Live;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\postJson;

function ingestPayload(array $overrides = []): array
{
    return array_merge([
        'user' => 'he4rt',
        'password' => '',
        'ip' => '172.18.0.1',
        'action' => 'publish',
        'path' => 'live',
        'protocol' => 'rtmp',
        'query' => '',
    ], $overrides);
}

it('autoriza leitura sem credenciais', function (): void {
    postJson('/live/ingest/auth', ingestPayload(['action' => 'read', 'protocol' => 'hls']))
        ->assertNoContent();
});

it('autoriza publish com a key da live corrente', function (): void {
    $live = Live::factory()->create();

    postJson('/live/ingest/auth', ingestPayload(['password' => $live->stream_key]))
        ->assertNoContent();
});

it('recusa publish com key incorreta', function (): void {
    Live::factory()->create();

    postJson('/live/ingest/auth', ingestPayload(['password' => 'chave-errada']))
        ->assertForbidden();
});

it('recusa publish quando não existe live corrente', function (): void {
    Live::factory()->ended()->create();

    postJson('/live/ingest/auth', ingestPayload(['password' => 'qualquer']))
        ->assertForbidden();
});

it('recusa publish em path desconhecido mesmo com key correta', function (): void {
    $live = Live::factory()->create();

    postJson('/live/ingest/auth', ingestPayload(['password' => $live->stream_key, 'path' => 'outro']))
        ->assertForbidden();
});

it('recusa ações desconhecidas', function (): void {
    postJson('/live/ingest/auth', ingestPayload(['action' => 'sabotage']))
        ->assertForbidden();
});

it('aplica rate limit por IP após falhas repetidas de publish', function (): void {
    RateLimiter::clear('live-publish:172.18.0.9');
    $live = Live::factory()->create();

    foreach (range(1, 5) as $i) {
        postJson('/live/ingest/auth', ingestPayload(['password' => 'errada', 'ip' => '172.18.0.9']))
            ->assertForbidden();
    }

    postJson('/live/ingest/auth', ingestPayload(['password' => $live->stream_key, 'ip' => '172.18.0.9']))
        ->assertForbidden();
});
