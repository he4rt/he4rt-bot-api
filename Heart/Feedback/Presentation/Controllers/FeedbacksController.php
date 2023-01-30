<?php

namespace Heart\Feedback\Presentation\Controllers;

use Heart\Feedback\Application\ReviewFeedback;
use Heart\Feedback\Domain\Actions\CreateFeedback;
use Heart\Feedback\Domain\Actions\GetFeedbackById;
use Heart\Feedback\Presentation\Requests\CreateFeedbackRequest;
use Heart\Feedback\Presentation\Requests\FeedbackReviewRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FeedbacksController
{
    public function getFeedback(
        string $feedbackId,
        GetFeedbackById $getFeedbackById,
    ): JsonResponse {
        return response()->json(
            $getFeedbackById->handle($feedbackId)
        );
    }

    public function postFeedback(CreateFeedbackRequest $request, CreateFeedback $create): JsonResponse
    {
        return response()->json($create->handle($request->validated()), Response::HTTP_CREATED);
    }

    public function postReview(
        FeedbackReviewRequest $request,
        string $feedbackId,
        string $reviewType,
        ReviewFeedback $review,
    ): JsonResponse {
        $review->handle($feedbackId, $reviewType, $request->input('staff_id'), $request->input('reason'));

        return response()->json(
            ['message' => 'Feedback recebido com sucesso!'],
            Response::HTTP_CREATED
        );
    }
}
