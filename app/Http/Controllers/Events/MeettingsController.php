<?php

namespace App\Http\Controllers\Events;

use App\Actions\Event\Meeting\AttendMeeting;
use App\Actions\Event\Meeting\CreateMeeting;
use App\Actions\Event\Meeting\IndexMeeting;
use App\Actions\Event\Meeting\UpdateMeeting;
use App\Exceptions\MeetingsException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MeettingsController extends Controller
{
    public function index(IndexMeeting $action): JsonResponse
    {
        return response()->json($action->handle());
    }

    public function store(Request $request, CreateMeeting $action): JsonResponse
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

    public function endMeeting(Request $request, int $meetingId, UpdateMeeting $action): JsonResponse
    {
        $request->merge(['meeting_id' => $meetingId]);
        $payload = $this->validate($request, [
            'meeting_id' => ['required', 'integer', 'exists:meetings,id']
        ]);

        return response()->json($action->handle($meetingId, $payload));
    }

    public function attendMeeting(Request $request, AttendMeeting $action): JsonResponse
    {
        $payload = $this->validate($request, [
            'meeting_id' => ['required', 'integer', 'exists:meetings,id'],
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
}
