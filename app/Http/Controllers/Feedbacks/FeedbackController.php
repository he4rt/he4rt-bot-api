<?php

namespace App\Http\Controllers\Feedbacks;

use App\Actions\Feedback\CreateFeedback;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedbackController extends Controller
{
    /** @throws UserException */
    public function create(Request $request, CreateFeedback $create): JsonResponse
    {
        $payload = $this->validate($request, [
            'sender_id' => [
                'required',
                'numeric',
                'different:target_id',
            ],
            'target_id' => [
                'required',
                'numeric',
                'different:sender_id',
            ],
            'message' => [
                'string',
                'max:4000'
            ],
            'type' => [
                'required',
                'string'
            ],
        ]);

        return response()->json($create->handle($payload)->toArray(), Response::HTTP_CREATED);
    }
}
