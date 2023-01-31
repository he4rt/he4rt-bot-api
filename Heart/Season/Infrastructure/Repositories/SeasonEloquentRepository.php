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

    public function __construct(private readonly Season $model, private readonly SeasonCollection $seasonCollection)
    {
        $this->query = $this->model->newQuery();
    }

    public function getAll(): array
    {
        $collection = $this->query->get();

        return $this->seasonCollection::make($collection->toArray())
            ->jsonSerialize();
    }

    public function getCurrent(): SeasonEntity
    {
        $model = $this->query->find(config('he4rt.season.id'));

        return SeasonEntity::make($model->toArray());
    }
}
