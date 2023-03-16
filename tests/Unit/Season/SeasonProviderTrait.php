<?php

namespace Tests\Unit\Season;

use Carbon\Carbon;
use Heart\Season\Domain\Entities\SeasonEntity;

trait SeasonProviderTrait
{
    public function validSeasonPayload(array $fields = []): array
    {
        return [
            'id' => 'canhassi-id',
            'name' => 'canhassi',
            'description' => 'é o canhas tropinha! famoso 3k',
            'messages_count' => 12121,
            'participants_count' => 1212,
            'meeting_count' => 12,
            'badges_count' => 12,
            'started_at' => Carbon::now()->toString(),
            'ended_at' => Carbon::now()->toString(),
            ...$fields,
        ];
    }

    public function validSeasonEntity(): SeasonEntity
    {
        return SeasonEntity::make($this->validSeasonPayload());
    }
}
