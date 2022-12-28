<?php

namespace App\Actions\User;

use App\Exceptions\UserException;
use App\Models\User\User;
use App\Repositories\Users\UsersRepository;

class GetUser
{
    private UsersRepository $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @throws UserException */
    public function handle(string $discordId): User
    {
        if (!$user = $this->repository->findByIdWithLoad($discordId)) {
            throw UserException::discordIdNotFound($discordId);
        }

        return $user;
    }
}
