<?php

namespace Heart\Message\Application;

use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\IncrementExperience;
use Heart\Message\Domain\DTO\NewVoiceMessageDTO;
use Heart\Message\Domain\Repositories\VoiceRepository;
use Heart\Provider\Application\FindProvider;

class NewVoiceMessage
{
    public function __construct(
        private readonly FindProvider $findProvider,
        private readonly FindCharacterIdByUserId $findCharacterId,
        private readonly IncrementExperience $characterExperience,
        private readonly VoiceRepository $voiceRepository
    ) {
    }

    public function persist(array $payload): void
    {
        $voiceDTO = NewVoiceMessageDTO::make($payload);
        $provider = $this->findProvider->handle(
            $voiceDTO->provider->value,
            $voiceDTO->providerId
        );

        $characterId = $this->findCharacterId->handle($provider->userId);
        $obtainedExperience = $this->characterExperience->incrementByVoiceMessage(
            $characterId,
            $voiceDTO->voiceState
        );

        $this->voiceRepository->create($voiceDTO, $provider->id, $obtainedExperience);
    }
}
