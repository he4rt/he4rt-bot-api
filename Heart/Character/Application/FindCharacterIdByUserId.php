<?php

namespace Heart\Character\Application;

use Heart\Character\Domain\Actions\GetCharacterByUserId;
use Heart\Shared\Application\TTL;
use Illuminate\Support\Facades\Cache;

class FindCharacterIdByUserId
{
    public function __construct(
        protected readonly GetCharacterByUserId $finder
    ) {
    }

    public function handle(string $userId): string
    {
        $cacheCharacterKey = sprintf('user-%s-character-id', $userId);

        return Cache::remember(
            $cacheCharacterKey,
            TTL::fromDays(2),
            fn () => $this->findCharacterByUserId($userId)
        );
    }

    private function findCharacterByUserId(string $userId): string
    {
        return $this->finder->handle($userId)->id;
    }
}
