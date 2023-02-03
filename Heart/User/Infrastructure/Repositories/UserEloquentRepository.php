<?php

namespace Heart\User\Infrastructure\Repositories;

use Heart\Shared\Domain\Paginator;
use Heart\Shared\Infrastructure\Paginator as PaginatorConcrete;
use Heart\User\Domain\Entities\ProfileEntity;
use Heart\User\Domain\Entities\UserEntity;
use Heart\User\Domain\Exceptions\UserEntityException;
use Heart\User\Domain\Repositories\UserRepository;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Ramsey\Uuid\Uuid;

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
    public function find(string $id): UserEntity
    {
        $user = $this->query->find($id)->toArray();

        return UserEntity::fromArray($user);
    }

    public function findByUsername(string $username): ?UserEntity
    {
        $user = $this->query->where('username', $username)
            ->first();

        if (!$user) {
            return null;
        }

        return UserEntity::fromArray($user->toArray());
    }

    public function findProfile(string $userId): ProfileEntity
    {
        $user = $this->query->newQuery()
            ->with([
                'character',
                'providers',
                'information',
                'character.badges',
                'address',
                'character.pastSeasons',
            ])->find($userId);

        return ProfileEntity::make($user->toArray());
    }

    public function createUser(string $username): UserEntity
    {
        $model = $this->model
            ->newQuery()
            ->create([
                'id' => Uuid::uuid4()->toString(),
                'username' => $username,
                'is_donator' => false,
            ]);

        $model->address()->create();
        $model->information()->create();

        return UserEntity::fromArray($model->toArray());
    }

    public function updateProfile(ProfileEntity $profileEntity): ProfileEntity
    {
        $model = $this->model
            ->newQuery()
            ->find($profileEntity->id);

        $model->information()->update($profileEntity->informationEntity->jsonSerialize());

        return $this->findProfile($profileEntity->id);
    }
}
