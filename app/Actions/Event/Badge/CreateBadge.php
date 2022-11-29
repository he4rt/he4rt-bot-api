<?php

namespace App\Actions\Event\Badge;

use App\Models\Events\Badge;
use App\Repositories\Events\BadgeRepository;

class CreateBadge
{
    private BadgeRepository $repository;

    public function __construct(BadgeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(array $payload): Badge
    {
        return $this->repository->create($payload);
    }
}
