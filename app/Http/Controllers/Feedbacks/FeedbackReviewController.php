<?php

namespace App\Http\Controllers\Feedbacks;

use App\Actions\Feedback\Review\ApproveFeedback;
use App\Actions\Feedback\Review\DeclineFeedback;
use App\Exceptions\FeedbackException;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackReviewController extends Controller
{
    /**
     * @throws UserException
     * @throws FeedbackException
     */
    public function approve(Request $request, int $feedbackId, ApproveFeedback $approve): JsonResponse
    {
        $payload = $request->validate([
            'staff_id' => [
                'required',
                'numeric',
            ],
            'feedback_id' => [
                'required',
                'numeric',
            ],
        ]);

        $approve->handle($feedbackId, $payload);

        return response()->json(['message' => 'Feedback approved.']);
    }

    /**
     * @throws UserException
     * @throws FeedbackException
     */
    public function decline(Request $request, int $feedbackId, DeclineFeedback $decline)
    {
        $payload = $request->validate([
             'staff_id' => [
                'required',
                'numeric',
            ],
            'feedback_id' => [
                'required',
                'numeric',
            ],
            'decline_message' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ]);

        $decline->handle($feedbackId, $payload);

        return response()->json(['message' => 'Feedback declined.']);
    }
}
