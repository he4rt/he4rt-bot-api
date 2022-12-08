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
    public function approve(Request $request, int $feedbackId, ApproveFeedback $approve): JsonResponse
    {
        $payload = $this->validate($request, [
            'staff_id' => [
                'required',
                'numeric',
            ],
        ]);

        try {
            $approve->handle($feedbackId, $payload);
        } catch (UserException | FeedbackException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

        return response()->json(['message' => 'Feedback approved.']);
    }

    public function decline(Request $request, int $feedbackId, DeclineFeedback $decline)
    {
        $payload = $this->validate($request, [
            'staff_id' => [
                'required',
                'numeric',
            ],
            'decline_message' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            $decline->handle($feedbackId, $payload);
        } catch (UserException | FeedbackException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }


        return response()->json(['message' => 'Feedback declined.']);
    }
}
