<?php

namespace App\Actions\Event\Badge;

use App\Repositories\Events\BadgeRepository;
use App\Repositories\Users\UsersRepository;

class ClaimBadge
{
    private UsersRepository $usersRepository;
    private BadgeRepository $badgeRepository;

    public function __construct(
        UsersRepository $usersRepository,
        BadgeRepository $badgeRepository
    )
    {
        $this->usersRepository = $usersRepository;
        $this->badgeRepository = $badgeRepository;
    }

    public function handle(string $discordId, string $redeemCode)
    {
        $badge = $this->badgeRepository->findByRedeemCode($redeemCode);

        $this->usersRepository->attachBadge($discordId, $badge->getKey());

        return sprintf('You got the %s badge. Congratz!', $badge->name);
    }
}
