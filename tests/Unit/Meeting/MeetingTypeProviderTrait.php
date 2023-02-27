<?php

namespace Tests\Unit\Meeting;

use Carbon\Carbon;
use Heart\Meeting\Domain\Entities\MeetingTypeEntity;

trait MeetingTypeProviderTrait
{
    public function validMeetingPayload(array $fields = []): array
    {
        return [
            'id'      => 12,
            'name'    => 'canhassi',
            'week_day' => 1,
            'start_at' => Carbon::now()
        ];
    }

    public function validMeetingTypeEntity(): MeetingTypeEntity
    {
        return MeetingTypeEntity::make($this->validMeetingPayload());
    }
}
