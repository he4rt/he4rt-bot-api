<?php

namespace Heart\Message\Infrastructure\Repositories;

use Heart\Message\Domain\DTO\NewVoiceMessageDTO;
use Heart\Message\Domain\Entities\VoiceEntity;
use Heart\Message\Domain\Repositories\VoiceRepository;
use Heart\Message\Infrastructure\Models\Voice;

class VoiceEloquentRepository implements VoiceRepository
{
    public function __construct(private readonly Voice $model)
    {
    }

    public function create(NewVoiceMessageDTO $messageDTO, string $providerId, int $obtainedExperience): VoiceEntity
    {
        $model = $this->model->newQuery()->create([
            'provider_id' => $providerId,
            'season_id' => config('he4rt.season.id'),
            'channel_name' => $messageDTO->channelName,
            'state' => $messageDTO->voiceState->value,
            'obtained_experience' => $obtainedExperience
        ]);

        return VoiceEntity::make($model->toArray());
    }
}
