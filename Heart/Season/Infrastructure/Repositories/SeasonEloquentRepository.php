<?php

namespace Heart\Season\Infrastructure\Repositories;

use Heart\Season\Domain\Collections\SeasonCollection;
use Heart\Season\Domain\Entities\SeasonEntity;
use Heart\Season\Domain\Repositories\SeasonRepository;
use Heart\Season\Infrastructure\Models\Season;
use Illuminate\Database\Eloquent\Builder;

class SeasonEloquentRepository implements SeasonRepository
{
    private Builder $query;

    public function __construct(private readonly Season $model)
    {
        $this->query = $this->model->newQuery();
    }

    public function getAll(): SeasonCollection
    {
        $collection = $this->query->get();

        return SeasonCollection::make($collection->toArray());
    }

    public function getCurrent(): SeasonEntity
    {
        $model = $this->query->find(config('he4rt.season.id'));

        return SeasonEntity::make($model->toArray());
    }
}
