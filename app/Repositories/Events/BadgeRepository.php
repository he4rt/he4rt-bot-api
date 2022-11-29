<?php

namespace App\Repositories\Events;

use App\Models\Events\Badge;

class BadgeRepository
{
    public function create(array $payload): Badge
    {
        return Badge::create($payload);
    }

    public function findByRedeemCode(string $redeemCode): Badge
    {
        return Badge::where('redeem_code', $redeemCode)->first();
    }
}
