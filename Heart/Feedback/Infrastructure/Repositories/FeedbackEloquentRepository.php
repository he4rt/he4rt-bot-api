<?php

namespace Heart\Feedback\Infrastructure\Repositories;

use Heart\Feedback\Domain\DTOs\FeedbackReviewDTO;
use Heart\Feedback\Domain\DTOs\NewFeedbackDTO;
use Heart\Feedback\Domain\Entities\FeedbackEntity;
use Heart\Feedback\Domain\Repositories\FeedbackRepository;
use Heart\Feedback\Infrastructure\Exceptions\FeedbackException;
use Heart\Feedback\Infrastructure\Models\Feedback;

class FeedbackEloquentRepository implements FeedbackRepository
{
    public function __construct(private readonly Feedback $model)
    {
    }

    public function findById(string $id): FeedbackEntity
    {
        $model = $this->model
            ->newQuery()
            ->find($id);

        if (!$model) {
            throw FeedbackException::idNotFound($id);
        }

        return FeedbackEntity::make($model->toArray());
    }

    public function create(NewFeedbackDTO $dto): FeedbackEntity
    {
        $model = $this->model->newQuery()->create($dto->jsonSerialize());

        return FeedbackEntity::make($model->toArray());
    }

    public function reviewFeedback(FeedbackReviewDTO $dto)
    {
        return $this->model->newQuery()
            ->find($dto->feedbackId)
            ->review()
            ->create([
                ...$dto->jsonSerialize(),
                'received_at' => now(),
            ]);
    }
}
