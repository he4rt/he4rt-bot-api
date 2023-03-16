<?php

namespace Heart\Meeting\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Meeting\Application\EndMeeting;
use Heart\Meeting\Application\PaginateMeetings;
use Heart\Meeting\Application\StartMeeting;
use Heart\Meeting\Domain\Exceptions\MeetingException;
use Heart\Meeting\Presentation\Requests\MeetingRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MeetingController extends Controller
{
    public function getMeetings(string $provider, PaginateMeetings $paginateMeetings): JsonResponse
    {
        return response()->json($paginateMeetings->handle($provider));
    }

    public function postMeeting(
        string $provider,
        MeetingRequest $request,
        StartMeeting $startMeeting
    ): JsonResponse {
        try {
            return response()->json(
                $startMeeting->handle($provider, $request->input('provider_id'), $request->input('meeting_type_id')),
                Response::HTTP_CREATED
            );
        } catch (MeetingException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function postEndMeeting(
        string $provider,
        EndMeeting $endMeeting,
    ): Response {
        $endMeeting->handle();

        return response()->noContent();
    }
}
