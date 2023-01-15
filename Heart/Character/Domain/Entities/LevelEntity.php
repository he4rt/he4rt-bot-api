<?php

namespace Heart\Character\Domain\Entities;

class LevelEntity
{
    private array $experienceTable = [
        0, 89, 178, 267, 356, 445, 534, 623, 712, 801, 890,
        726, 729, 858, 924, 990, 1056, 1122, 1188, 1254, 1320,
    ];

    public int $level;
    public int $experience;

    public function __construct(int $experience)
    {
        $this->experience = $experience;
        $this->setCurrentLevel($experience);
    }

    public function generateMessageExperience(): int
    {
        return 1;
    }

    private function setCurrentLevel(int $currentExperience): void
    {
        $experienceNeeded = 0;
        foreach ($this->experienceTable as $level => $experience) {
            $experienceNeeded += $experience;
            if ($currentExperience <= $experienceNeeded) {
                $this->level = $level;
                return;
            }
        }
    }
}
