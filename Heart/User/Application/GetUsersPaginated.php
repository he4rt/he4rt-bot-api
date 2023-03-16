<?php

namespace Heart\User\Application;

use Heart\Shared\Domain\Paginator;
use Heart\User\Domain\Repositories\UserRepository;

class GetUsersPaginated
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    public function handle(): Paginator
    {
        return $this->repository
            ->paginated();
    }
}
