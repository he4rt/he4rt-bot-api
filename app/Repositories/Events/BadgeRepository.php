<?php

namespace App\Repositories\Events;

use App\Models\Events\Badge;

class BadgeRepository
{
    public function create(array $payload): Badge
    {
        return Badge::create($payload);
    }
}
