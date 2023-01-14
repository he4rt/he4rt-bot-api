<?php

namespace Heart\Character\Domain\Entities;

class CharacterEntity
{
    public int $id;
    public string $userId;
    public LevelEntity $level;
    public DailyReward $dailyReward;

    public function __construct(string $id, string $userId, int $experience, string $claimedAt)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->level = new LevelEntity($experience);
        $this->dailyReward = new DailyReward($claimedAt);
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            userId: $payload['user_id'],
            experience: $payload['experience'],
            claimedAt: $payload['daily_bonus_claimed_at']
        );
    }
}
