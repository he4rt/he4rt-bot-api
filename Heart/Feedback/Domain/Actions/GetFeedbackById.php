<?php

namespace Heart\Feedback\Domain\Actions;

use Heart\Feedback\Domain\Entities\FeedbackEntity;
use Heart\Feedback\Domain\Repositories\FeedbackRepository;

class GetFeedbackById
{
    private FeedbackRepository $repository;

    public function __construct(FeedbackRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(string $id): FeedbackEntity
    {
        return $this->repository->findById($id);
    }
}
