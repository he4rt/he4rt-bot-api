<?php

namespace Heart\Character\Domain\Entities;

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

    public function getExperienceNeededToLevelUp(): int
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
        $experienceObtained = ($messagePound / ($this->level * $memberStatusMultiplier) * 20);
        $this->addExperience($experienceObtained);

        return (int) $experienceObtained;
    }

    private function getLevelExpoent(): float
    {
        return match (true) {
            $this->level >= 1 && $this->level <= 10 => 1.5,
            $this->level >= 11 && $this->level <= 20 => 1.4,
            $this->level >= 21 && $this->level <= 30 => 1.3,
            $this->level >= 31 && $this->level <= 40 => 1.4,
            $this->level >= 41 && $this->level <= 50 => 1.5,
            $this->level >= 50 => 2.0
        };
    }

    public function getExperience(): int
    {
        return $this->experience;
    }
}
