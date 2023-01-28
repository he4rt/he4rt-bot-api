<?php

namespace Heart\Feedback\Domain\Actions;

class ApproveFeedback
{
    private FeedbackReviewRepository $feedbackReviewRepository;
    private GetFeedback $getFeedback;
    private GetUser $getUser;

    public function __construct(
        FeedbackReviewRepository $feedbackReviewRepository,
        GetFeedback $getFeedback,
        GetUser $getUser
    ) {
        $this->feedbackReviewRepository = $feedbackReviewRepository;
        $this->getFeedback = $getFeedback;
        $this->getUser = $getUser;
    }

    /**
     * @throws \App\Exceptions\FeedbackException
     * @throws \App\Exceptions\UserException
     */
    public function handle(int $feedbackId, string $staffId): FeedbackReview
    {
        return $this->feedbackReviewRepository->create([
            'feedback_id' => $this->getFeedback->handle($feedbackId)->getKey(),
            'staff_id'    => $this->getUser->handle($staffId)->getKey(),
            'approved_at' => Carbon::now(),
        ]);
    }
}
