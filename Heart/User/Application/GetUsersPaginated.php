<?php

namespace Heart\User\Application;

use Heart\User\Domain\Repositories\UserRepository;

class GetUsersPaginated
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    public function handle(): array
    {
        return $this->repository
            ->paginated()
            ->get();
    }
}
