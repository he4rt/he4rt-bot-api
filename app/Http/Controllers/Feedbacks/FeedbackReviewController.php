<?php

namespace App\Http\Controllers\Feedbacks;

use App\Actions\Feedback\Review\ApproveFeedback;
use App\Actions\Feedback\Review\DeclineFeedback;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackReviewController extends Controller
{
    public function approve(Request $request, ApproveFeedback $approve): JsonResponse
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

        $approve->handle($payload);

        return response()->json(['message' => 'Feedback was successfully approved.']);
    }

    public function decline(Request $request, DeclineFeedback $decline)
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

        $decline->handle($payload);
    }
}
