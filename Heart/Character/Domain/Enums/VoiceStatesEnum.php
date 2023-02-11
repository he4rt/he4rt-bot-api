<?php

namespace Heart\Character\Domain\Enums;

enum VoiceStatesEnum: string
{
    case Disabled = 'disabled';
    case Muted = 'muted';
    case Unmuted = 'unmuted';

    public function getExperienceMultiplier(): int
    {
        return match ($this) {
            self::Disabled => 0,
            self::Muted => 3,
            self::Unmuted => 5,
        };
    }
}
