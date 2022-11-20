<?php

namespace App\Actions\User;

use App\Exceptions\DailyRewardException;
use App\Models\User\User;
use App\Repositories\Users\UsersRepository;
use Carbon\Carbon;

class DailyUserPoints
{
    private UsersRepository $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @throws DailyRewardException
     */
    public function handle(string $discordId, bool $isDonator): array
    {
        $user = $this->repository->findById($discordId);
        $nextRedeemDate = $user->daily;

        if (!$this->isAlreadyTimeToRedeemPoints($nextRedeemDate)) {
            throw DailyRewardException::alreadyRedeemed($nextRedeemDate);
        }

        $points = $this->giveUserPoints($user, $isDonator);
        return $this->transformResult($user, $points);
    }

    private function isAlreadyTimeToRedeemPoints(?Carbon $lastRedeemDate): bool
    {
        return is_null($lastRedeemDate) || $lastRedeemDate->isPast();
    }

    private function generateDailyPoints(bool $donator): int
    {
        $points = rand(300, 600);
        return $donator ? ($points * 2) : $points;
    }

    private function giveUserPoints(User $user, bool $isDonator): int
    {
        $points = $this->generateDailyPoints($isDonator);
        $user->dailyPoints($points);
        return $points;
    }


    private function transformResult(User $user, int $points): array
    {
        return [
            'points' => $points,
            'date' => $user->daily
        ];
    }
}
