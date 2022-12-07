<?php

namespace App\Http\Controllers\Events;

use App\Actions\Event\Meeting\CreateMeeting;
use App\Actions\Event\Meeting\UpdateMeeting;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class MeettingsController extends Controller
{
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
            'meeting_id' => ['required', 'exists:meetings,id'],
        ]);

        return response()->json($action->handle($meetingId, $payload));
    }
}
