<?php

declare(strict_types=1);

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
        $payload = $this->validate($request, [
            'staff_id' => [
                'required',
                'numeric',
            ],
        ]);

        $approve->handle($feedbackId, $payload);

        return response()->json(['message' => trans('feedbacks.approved')]);
    }

    /**
     * @throws UserException
     * @throws FeedbackException
     */
    public function decline(Request $request, int $feedbackId, DeclineFeedback $decline): JsonResponse
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

        $decline->handle($feedbackId, $payload);

        return response()->json(['message' => trans('feedbacks.declined')]);
    }
}
