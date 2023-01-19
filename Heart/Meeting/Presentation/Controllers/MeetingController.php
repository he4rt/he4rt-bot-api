<?php

namespace Heart\Meeting\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Meeting\Application\CreateMeeting;
use Heart\Meeting\Application\PaginateMeetings;
use Heart\Meeting\Presentation\Requests\CreateMeetingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MeetingController extends Controller
{
    public function getMeetings(string $provider, PaginateMeetings $action): JsonResponse
    {
        return response()->json($action->handle($provider));
    }


    public function postMeeting(CreateMeetingRequest $request, CreateMeeting $action): JsonResponse
    {
        return response()->json(
            $action->handle($request->input('meeting_type_id'), $request->input('discord_id')),
            Response::HTTP_CREATED
        );
    }


    //
    // public function postEndMeeting(EndMeeting $action): JsonResponse
    // {
    // return response()->json(['message' => $action->handle()]);
    // }
    //
    // public function postAttendMeeting(Request $request, AttendMeeting $action): JsonResponse
    // {
    // $payload = $this->validate($request, [
    // 'discord_id' => ['required', 'integer', 'exists:users']
    // ]);
    //
    // try {
    // $action->handle($payload);
    // return response()->json(
    // ['message' => __('meetings.success.attendMeeting')],
    // Response::HTTP_CREATED
    // );
    // } catch (MeetingsException $e) {
    // return response()->json(['message' => $e->getMessage()], $e->getCode());
    // }
    // }
    //
    // public function postMeetingSubject(Request $request, int $meetingId, AddMeetingSubject $action): JsonResponse
    // {
    // $payload = $this->validate($request, [
    // 'content' => ['required', 'string']
    // ]);
    //
    // return response()->json($action->handle($meetingId, $payload));
    // }
}//end class
