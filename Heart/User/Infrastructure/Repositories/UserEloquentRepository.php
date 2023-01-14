<?php

namespace Heart\User\Infrastructure\Repositories;

use Heart\User\Domain\Entity\UserEntity;
use Heart\User\Domain\Exceptions\UserEntityException;
use Heart\User\Domain\Repositories\UserRepository;
use Heart\User\Domain\ValueObjects\UserId;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserEloquentRepository implements UserRepository
{
    private Builder $query;

    public function __construct(private readonly User $model)
    {
        $this->query = $this->model->newQuery();
    }

    public function get(): array
    {
        return $this->query->get()->jsonSerialize();
    }

    public function paginated(bool $shouldPaginate = true, ?int $perPage = null): self
    {
        $this->query->paginate($perPage ?? 15);

        return $this;
    }

    /** @throws UserEntityException */
    public function find(UserId $id): UserEntity
    {
        $user = $this->query->find($id->value())->toArray();

        return UserEntity::fromArray($user);
    }
}
