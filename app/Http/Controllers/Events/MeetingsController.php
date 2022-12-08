<?php

namespace App\Http\Controllers\Events;

use App\Actions\Event\Meeting\AddMeetingSubject;
use App\Actions\Event\Meeting\AttendMeeting;
use App\Actions\Event\Meeting\CreateMeeting;
use App\Actions\Event\Meeting\IndexMeeting;
use App\Actions\Event\Meeting\EndMeeting;
use App\Exceptions\MeetingsException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MeetingsController extends Controller
{
    public function getMeetings(IndexMeeting $action): JsonResponse
    {
        return response()->json($action->handle());
    }

    public function postMeeting(Request $request, CreateMeeting $action): JsonResponse
    {
        $payload = $this->validate($request, [
            'meeting_type_id' => ['required', 'integer', 'exists:meeting_types,id'],
            'discord_id' => ['required', 'integer', 'exists:users']
        ]);

        return response()->json(
            $action->handle($payload),
            Response::HTTP_CREATED
        );
    }

    public function postEndMeeting(EndMeeting $action): JsonResponse
    {
        return response()->json(['message' => $action->handle()]);
    }

    public function postAttendMeeting(Request $request, AttendMeeting $action): JsonResponse
    {
        $payload = $this->validate($request, [
            'discord_id' => ['required', 'integer', 'exists:users']
        ]);

        try {
            return response()->json(
                $action->handle($payload),
                Response::HTTP_CREATED
            );
        } catch (MeetingsException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function postMeetingSubject(Request $request, int $meetingId, AddMeetingSubject $action): JsonResponse
    {
        $payload = $this->validate($request, [
            'content' => ['required', 'string']
        ]);

        return response()->json($action->handle($meetingId, $payload));
    }
}
