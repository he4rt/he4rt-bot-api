<?php

namespace Heart\Feedback\Presentation\Controllers;

use Heart\Feedback\Domain\Actions\ApproveFeedback;
use Heart\Feedback\Domain\Actions\CreateFeedback;
use Heart\Feedback\Domain\Actions\DeclineFeedback;
use Heart\Feedback\Presentation\Requests\ApproveFeedbackRequest;
use Heart\Feedback\Presentation\Requests\CreateFeedbackRequest;
use Heart\Feedback\Presentation\Requests\DeclineFeedbackRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FeedbacksController
{
    public function create(CreateFeedbackRequest $request, CreateFeedback $create): JsonResponse
    {
        try {
            return response()->json($create->handle($request->validated()), Response::HTTP_CREATED);
        } catch (UserException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function approve(ApproveFeedbackRequest $request, int $feedbackId, ApproveFeedback $approve): JsonResponse
    {
        $approve->handle($feedbackId, $request->input('staff_id'));

        return response()->json(['message' => trans('feedbacks.approved')]);
    }

    public function decline(DeclineFeedbackRequest $request, int $feedbackId, DeclineFeedback $decline): JsonResponse
    {
        $decline->handle($feedbackId, $request->input('staff_id'), $request->input('decline_message'));

        return response()->json(['message' => trans('feedbacks.declined')]);
    }
}
