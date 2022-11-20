<?php

namespace App\Actions\User;

use App\Repositories\Users\UsersRepository;

class DeleteUser
{
    private UsersRepository $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(string $discordId): ?bool
    {
        return $this->repository->findById($discordId)->delete();
    }
}
