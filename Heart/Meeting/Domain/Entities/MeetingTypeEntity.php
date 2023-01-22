<?php

namespace Heart\Meeting\Domain\Entities;

use DateTime;

class MeetingTypeEntity
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $weekDay,
        public readonly DateTime $startAt
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            name: $payload['name'],
            weekDay: $payload['week_day'],
            startAt: new DateTime($payload['start_at']),
        );
    }

    public function getMeetingDayForHumans(): string
    {
        $days = [
            0 => 'Domingo',
            1 => 'Segunda Feira',
            2 => 'Terça Feira',
            3 => 'Quarta Feira',
            4 => 'Quinta Feira',
            5 => 'Sexta Feira',
            6 => 'Sábado',
        ];

        return $days[$this->weekDay];
    }
}
