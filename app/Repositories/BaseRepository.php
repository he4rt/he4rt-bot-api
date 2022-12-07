<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T of Illuminate\Database\Eloquent\Model
 */
abstract class BaseRepository
{
    protected Model $model;

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function count(): int
    {
        return $this->model->count();
    }

    public function create(array $payload): Model
    {
        return $this->model->create($payload);
    }

    public function createMultiple(array $payload): Collection
    {
        $models = Collection::empty();

        foreach ($payload as $item) {
            $models->push($this->create($item));
        }

        return $models;
    }

    public function deleteById(int $id)
    {
        return $this->getById($id)->delete();
    }

    public function deleteMultipleById(array $ids)
    {
        return $this->model->destroy($ids);
    }

    public function first(): Model
    {
        return $this->model->first();
    }

    public function getById($id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function updateById(int $id, array $payload): Model
    {
        $record = $this->getById($id);

        $record->update($payload);

        return $record;
    }

    public function getWhere(string $column, string $value, string $operator = '=', array $relations = []): Collection
    {
        return $this->model
            ->with($relations)
            ->where($column, $value, $operator)
            ->get();
    }
}
