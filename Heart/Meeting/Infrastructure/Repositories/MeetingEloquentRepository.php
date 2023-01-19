<?php

namespace Heart\Meeting\Infrastructure\Repositories;

use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Meeting\Domain\Repositories\MeetingRepository;
use Heart\Meeting\Infrastructure\Models\Meeting;
use Heart\Shared\Domain\Paginator;
use Heart\Shared\Infrastructure\Paginator as PaginatorConcrete;

class MeetingEloquentRepository implements MeetingRepository
{
    public function __construct(private readonly Meeting $model)
    {
    }

    public function paginate(array $relations = [], int $perPage = 10): Paginator
    {
        $meetings =  $this->model->newQuery()->with($relations)->paginate($perPage);

        return PaginatorConcrete::paginate($meetings);
    }

    public function create(NewMeetingDTO $dto): MeetingEntity
    {
        $model =  $this->model->newQuery()->create([
            $dto
        ]);

        return PaginatorConcrete::paginate($meetings);
    }
}
