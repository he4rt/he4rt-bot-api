<?php

namespace Heart\Message\Domain\Repositories;

use Heart\Message\Domain\DTO\NewVoiceMessageDTO;
use Heart\Message\Domain\Entities\VoiceEntity;

interface VoiceRepository
{
    public function create(NewVoiceMessageDTO $messageDTO, string $providerId, int $obtainedExperience): VoiceEntity;
}
