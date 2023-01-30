<?php

namespace Heart\Feedback\Presentation\Controllers;

use Heart\Feedback\Application\ReviewFeedback;
use Heart\Feedback\Domain\Actions\ApproveFeedback;
use Heart\Feedback\Domain\Actions\CreateFeedback;
use Heart\Feedback\Domain\Actions\DeclineFeedback;
use Heart\Feedback\Domain\Enums\ReviewTypeEnum;
use Heart\Feedback\Presentation\Requests\FeedbackReviewRequest;
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
