<?php

namespace Heart\Message\Application;

use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\IncrementExperience;
use Heart\Meeting\Application\AttendMeeting;
use Heart\Message\Domain\Actions\PersistMessage;
use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Provider\Application\FindProvider;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Illuminate\Support\Facades\Cache;

class NewMessage
{
    public function __construct(
        private readonly PersistMessage $persistMessage,
        private readonly FindProvider $findProvider,
        private readonly FindCharacterIdByUserId $findCharacterId,
        private readonly IncrementExperience $characterExperience,
        private readonly AttendMeeting $attendMeeting,
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

        $this->meetingAttender($providerEntity, $messageDTO);
    }

    private function persistCharacterExperience(string $userId, string $content): int
    {
        $characterId = $this->findCharacterId->handle($userId);

        return $this->characterExperience->handle($characterId, $content);
    }

    private function meetingAttender(
        ProviderEntity $providerEntity,
        NewMessageDTO $messageDTO
    ): void {
        if (!Cache::has('current-meeting')) {
            return;
        }
        $userAttendedCacheKey = sprintf('meeting-%s-attended', $providerEntity->userId);
        if (Cache::has($userAttendedCacheKey)) {
            return;
        }

        $this->attendMeeting->handle($providerEntity->userId);
    }
}
