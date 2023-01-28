<?php

namespace Heart\Feedback\Domain\Actions;

class GetFeedbackById
{
    private FeedbackRepository $repository;

    public function __construct(FeedbackRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param int $id
     * @return Model
     * @throws FeedbackException
     */
    public function handle(int $id): Model
    {
        try {
            return $this->repository->getById($id);
        } catch (Exception $e) {
            throw FeedbackException::idNotFound($id);
        }
    }
}
