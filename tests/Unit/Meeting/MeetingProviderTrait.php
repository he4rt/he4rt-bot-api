<?php

namespace Tests\Unit\Meeting;

use Carbon\Carbon;
use Heart\Meeting\Domain\Entities\MeetingEntity;

trait MeetingProviderTrait
{
    public function validMeetingPayload(array $fields = []): array
    {
        return [
            'id'            => 12,
            'content'       => null,
            'meeting_type_id' => 12,
            'admin_id'       => "12",
            'starts_at'      => $time = Carbon::now(),
            'ends_at'        => $time->addMinutes(12),
            'created_at'     => Carbon::now(),
            'updated_at'     => Carbon::now()
        ];
    }

    public function validMeetingEntity(): MeetingEntity
    {
        return MeetingEntity::make($this->validMeetingPayload());
    }
}
