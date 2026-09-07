<?php

declare(strict_types=1);

namespace He4rt\Live\Actions;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use He4rt\Live\DTOs\StreamStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Consulta a Control API do mediamtx para saber se a live está no ar. */
final readonly class CheckLiveStatusAction
{
    public function execute(): StreamStatus
    {
        return Cache::remember(
            'live:stream-status',
            now()->addSeconds(5),
            fn (): StreamStatus => $this->fetch(),
        );
    }

    private function fetch(): StreamStatus
    {
        try {
            $response = Http::timeout(2)->get(sprintf(
                '%s/v3/paths/get/%s',
                config()->string('live.control_api_url'),
                config()->string('live.path'),
            ));
        } catch (ConnectionException) {
            return StreamStatus::offline();
        }

        if (!$response->ok() || $response->json('ready') !== true) {
            return StreamStatus::offline();
        }

        $readyTime = $response->json('readyTime');

        return new StreamStatus(
            onAir: true,
            startedAt: is_string($readyTime) ? $this->parseReadyTime($readyTime) : null,
        );
    }

    private function parseReadyTime(string $readyTime): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($readyTime);
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
