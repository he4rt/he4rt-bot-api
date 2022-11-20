<?php

namespace App\Actions\User;

use App\Models\User\User;
use App\Repositories\Users\UsersRepository;

class FindUser
{
    private UsersRepository $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(string $discordId): User
    {
        return $this->repository->findById($discordId);
    }
}
