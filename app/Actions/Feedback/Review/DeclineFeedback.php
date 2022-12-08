<?php

namespace App\Actions\Feedback\Review;

use App\Actions\Feedback\GetFeedback;
use App\Actions\User\GetUser;
use App\Exceptions\FeedbackException;
use App\Exceptions\UserException;
use App\Repositories\Feedback\FeedbackRepository;
use App\Repositories\Feedback\FeedbackReviewRepository;
use Carbon\Carbon;

class DeclineFeedback
{
    private FeedbackReviewRepository $feedbackReviewRepository;
    private GetFeedback $getFeedback;
    private GetUser $getUser;

    public function __construct(
        FeedbackReviewRepository $feedbackReviewRepository,
        GetFeedback              $getFeedback,
        GetUser                  $getUser,
    ) {
        $this->feedbackReviewRepository = $feedbackReviewRepository;
        $this->getFeedback = $getFeedback;
        $this->getUser = $getUser;
    }

    /**
     * @throws UserException
     * @throws FeedbackException
     */
    public function handle(array $payload): FeedbackRepository
    {
        return $this->feedbackReviewRepository->create([
            'feedback_id'     => $this->getFeedback->handle($payload['feedback_id'])->getKey(),
            'staff_id'        => $this->getUser->handle($payload['staff_id'])->getKey(),
            'decline_message' => $payload['decline_message'],
            'declined_at'     => Carbon::now(),
        ]);
    }
}
