<?php

namespace App\Actions\User;

use App\Exceptions\UserException;
use App\Models\User\User;
use App\Repositories\Users\UsersRepository;

class UpdateUser
{
    private UsersRepository $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @throws UserException */
    public function handle(string $discordId, array $payload): User
    {
        return $this->repository->update($discordId, $payload);
    }
}
