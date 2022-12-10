<?php

namespace App\Actions\Feedback;

use App\Actions\User\GetUser;
use App\Models\Feedback\Feedback;
use App\Repositories\Feedback\FeedbackRepository;

class CreateFeedback
{
    private FeedbackRepository $feedbackRepository;
    private GetUser $getUser;

    public function __construct(
        FeedbackRepository $feedbackRepository,
        GetUser $getUser
    ) {
        $this->feedbackRepository = $feedbackRepository;
        $this->getUser = $getUser;
    }

    /** @throws \App\Exceptions\UserException */
    public function handle(array $payload): Feedback
    {
        return $this->feedbackRepository->create([
            'sender_id' => $this->getUser->handle($payload['sender_id'])->getKey(),
            'target_id' => $this->getUser->handle($payload['target_id'])->getKey(),
            'message'   => $payload['message'],
            'type'      => $payload['type']
        ]);
    }
}
