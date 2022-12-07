<?php

namespace App\Http\Controllers\Events;

use App\Actions\Event\Meeting\CreateMeeting;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
