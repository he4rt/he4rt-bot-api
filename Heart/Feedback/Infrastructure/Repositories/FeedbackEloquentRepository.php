<?php

namespace Heart\Feedback\Infrastructure\Repositories;

use Heart\Feedback\Domain\DTOs\NewFeedbackDTO;
use Heart\Feedback\Domain\Entities\FeedbackEntity;
use Heart\Feedback\Domain\Repositories\FeedbackRepository;
use Heart\Feedback\Infrastructure\Models\Feedback;

class FeedbackEloquentRepository implements FeedbackRepository
{
    public function __construct(private readonly Feedback $model)
    {
    }

    public function create(NewFeedbackDTO $dto): FeedbackEntity
    {
        $model = $this->model->newQuery()->create($dto->jsonSerialize());

        return FeedbackEntity::make($model->toArray());
    }
}
