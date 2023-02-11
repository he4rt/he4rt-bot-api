<?php

namespace Heart\Message\Application;

use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\IncrementExperience;
use Heart\Message\Domain\DTO\NewVoiceMessageDTO;
use Heart\Provider\Application\FindProvider;

class NewVoiceMessage
{
    public function __construct(
        private readonly FindProvider $findProvider,
        private readonly FindCharacterIdByUserId $findCharacterId,
        private readonly IncrementExperience $characterExperience,
    )
    {
    }

    public function persist(array $payload): void
    {
        $voiceDTO = NewVoiceMessageDTO::make($payload);
        $provider = $this->findProvider->handle(
            $voiceDTO->provider->value,
            $voiceDTO->providerId
        );
        $characterId = $this->findCharacterId->handle($provider->userId);

        $this->characterExperience->incrementByVoiceMessage($characterId, $voiceDTO->voiceState);
    }
}
