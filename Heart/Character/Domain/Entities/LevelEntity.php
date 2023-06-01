<?php

namespace Heart\Character\Domain\Entities;

use Heart\Character\Domain\Enums\VoiceStatesEnum;

class LevelEntity
{
    private array $experienceTable = [
        0, 89, 178, 267, 356, 445, 534, 623, 712, 801, 890,
        726, 729, 858, 924, 990, 1056, 1122, 1188, 1254, 1320,
        1029, 1078, 1127, 1176, 1225, 1274, 1323, 1372, 1421, 1470,
        2046, 2112, 2178, 2244, 2310, 2376, 2442, 2508, 2574, 2640,
        3649, 3738, 3827, 3916, 4005, 4094, 4183, 4272, 4361, 4450,
    ];

    private int $level = 1;

    public function __construct(private int $experience)
    {
        $this->setCurrentLevel();
    }

    private function addExperience(int $experience): void
    {
        $this->experience += $experience;
        $this->setCurrentLevel();
    }

    private function setCurrentLevel(): void
    {
        $experienceNeeded = 0;
        foreach ($this->experienceTable as $level => $experience) {
            $experienceNeeded += $experience;
            if ($this->experience <= $experienceNeeded) {
                $this->level = $level;

                return;
            }
        }
    }

    private function getExperienceNeededToLevelUp(): int
    {
        $experienceNeeded = 0;
        foreach ($this->experienceTable as $index => $expTable) {
            if ($this->level + 1 == $index) {
                return $experienceNeeded;
            }
            $experienceNeeded += $expTable;
        }

        return $experienceNeeded;
    }

    public function getLevelUpStatus(): int
    {
        return $this->getExperienceNeededToLevelUp() - $this->experience;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function generateExperience(string $message, bool $isSupporter = false): int
    {
        $messageLength = strlen($message);
        $averageMessageLength = 25;
        $memberStatusMultiplier = $isSupporter ? 0.25 : 0.4;
        $messagePound = ($messageLength / $averageMessageLength);

        if ($this->level === 0) {
            $this->level += 1;
        }

        $experienceObtained = ($messagePound / ($this->level * $memberStatusMultiplier) * 20);
        $this->addExperience($experienceObtained);

        return (int) $experienceObtained;
    }

    public function generateVoiceExperience(VoiceStatesEnum $states, bool $isSupporter = false): int
    {
        $memberStatusMultiplier = $isSupporter ? 0.25 : 0.4;
        $experienceObtained = ($states->getExperienceMultiplier() / ($this->level * $memberStatusMultiplier) * 20);
        $this->addExperience($experienceObtained);

        return (int) $experienceObtained;
    }


    public function getExperience(): int
    {
        return $this->experience;
    }

    public function getPercentageExperience(): float
    {
        $levelMin = $this->getLevel() * 89;

        $difference =  $this->getExperience() - $levelMin;

        if ($difference == 0) {
            return 100.0;
        }

        return floor($difference / (100 * 89) * 100);
    }
}
