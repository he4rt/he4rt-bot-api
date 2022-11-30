<?php

namespace App\Actions\Event\Badge;

use App\Exceptions\BadgeException;
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

    public function handle(string $discordId, string $redeemCode): string
    {
        $badge = $this->badgeRepository->findByRedeemCode($redeemCode);
        $user = $this->usersRepository->findById($discordId);

        if ($user->hasBadge($badge->getKey())) {
            throw BadgeException::alreadyClaimed();
        }

        if (!$badge->canClaim()) {
            throw BadgeException::inactiveBadge();
        }

        $this->usersRepository->attachBadge($discordId, $badge->getKey());

        return __('badges.success', ['badgeName' => $badge->name]);
    }
}
