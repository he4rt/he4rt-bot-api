<?php

declare(strict_types=1);

namespace He4rt\Live\Actions;

use He4rt\Live\DTOs\IngestAuthPayload;
use He4rt\Live\Models\Live;
use Illuminate\Support\Facades\RateLimiter;

/** Decide se o mediamtx pode executar a ação: leitura é pública, publish exige a key da live corrente. */
final readonly class AuthorizeMediaServerAction
{
    private const int MAX_FAILURES_PER_MINUTE = 5;

    public function execute(IngestAuthPayload $payload): bool
    {
        return match ($payload->action) {
            'read', 'playback' => true,
            'publish' => $this->authorizePublish($payload),
            default => false,
        };
    }

    private function authorizePublish(IngestAuthPayload $payload): bool
    {
        $rateLimitKey = 'live-publish:'.$payload->ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_FAILURES_PER_MINUTE)) {
            return false;
        }

        if ($this->publishAllowed($payload)) {
            RateLimiter::clear($rateLimitKey);

            return true;
        }

        RateLimiter::hit($rateLimitKey, 60);

        return false;
    }

    private function publishAllowed(IngestAuthPayload $payload): bool
    {
        if ($payload->path !== config()->string('live.path')) {
            return false;
        }

        $live = Live::query()->current()->first();

        if ($live === null || $payload->password === '') {
            return false;
        }

        return hash_equals($live->stream_key, $payload->password);
    }
}
