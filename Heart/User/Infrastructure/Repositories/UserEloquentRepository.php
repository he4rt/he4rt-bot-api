<?php

namespace Heart\User\Infrastructure\Repositories;

use Heart\Shared\Domain\Paginator;
use Heart\Shared\Infrastructure\Paginator as PaginatorConcrete;
use Heart\User\Domain\Entities\UserEntity;
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

    public function paginated(int $perPage = 15): Paginator
    {
        $paginator = $this->query->paginate($perPage);

        return PaginatorConcrete::paginate($paginator);
    }

    /** @throws UserEntityException */
    public function find(UserId $id): UserEntity
    {
        $user = $this->query->find($id->value())->toArray();

        return UserEntity::fromArray($user);
    }
}
