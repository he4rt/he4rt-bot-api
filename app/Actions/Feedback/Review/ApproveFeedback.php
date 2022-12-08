<?php

namespace App\Actions\Feedback\Review;

use App\Actions\Feedback\GetFeedback;
use App\Actions\User\GetUser;
use App\Exceptions\FeedbackException;
use App\Exceptions\UserException;
use App\Models\Feedback\FeedbackReview;
use App\Repositories\Feedback\FeedbackRepository;
use App\Repositories\Feedback\FeedbackReviewRepository;
use App\Repositories\Users\UsersRepository;
use Carbon\Carbon;

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

    /** @throws FeedbackException
     * @throws UserException
     */
    public function handle(array $payload): FeedbackReview
    {
        return $this->feedbackReviewRepository->create([
            'feedback_id' => $this->getFeedback->handle($payload['feedback_id'])->getKey(),
            'staff_id'    => $this->getUser->handle($payload['staff_id'])->getKey(),
            'approved_at' => Carbon::now(),
        ]);
    }
}
