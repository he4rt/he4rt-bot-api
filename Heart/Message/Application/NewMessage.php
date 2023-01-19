<?php

namespace Heart\Message\Application;

use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\IncrementExperience;
use Heart\Message\Domain\Actions\PersistMessage;
use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Provider\Application\FindProvider;

class NewMessage
{
    public function __construct(
        private readonly PersistMessage $persistMessage,
        private readonly FindProvider $findProvider,
        private readonly FindCharacterIdByUserId $findCharacterId,
        private readonly IncrementExperience $characterExperience,
    ) {
    }

    public function handle(array $payload): void
    {
        $messageDTO = NewMessageDTO::make($payload);

        $providerEntity = $this->findProvider->handle(
            $messageDTO->provider->value,
            $messageDTO->providerId
        );

        $obtainedExperience = $this->persistCharacterExperience(
            $providerEntity->userId,
            $messageDTO->content
        );

        $this->persistMessage->handle(
            $messageDTO,
            $obtainedExperience,
            $providerEntity->id,
        );
    }

    private function persistCharacterExperience(string $userId, string $content): int
    {
        $characterId = $this->findCharacterId->handle($userId);

        return $this->characterExperience->handle($characterId, $content);
    }
}
