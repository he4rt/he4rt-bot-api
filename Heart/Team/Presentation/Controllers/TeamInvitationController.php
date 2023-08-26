<?php

namespace Heart\Team\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Team\Infrastructure\Models\Team;
use Heart\Team\Presentation\Requests\CreateInviteRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TeamInvitationController extends Controller
{
    public function postInvite(CreateInviteRequest $request, string $team): JsonResponse
    {
        if (!$currentTeam = Team::query()->find($team)) {
            return response()->json(['error' => 'not found'], Response::HTTP_NOT_FOUND);
        }

//        if (!$currentTeam = Team::query()->where('slug', $team)->first()) {
//            return response()->json(['error' => 'not found'], Response::HTTP_NOT_FOUND);
//        }

        if (!$currentTeam->isLeadership($request->input('invited_by'))) {
            return response()->json(['error' => 'youre not on the leadership of this team'], Response::HTTP_FORBIDDEN);
        }

        $response = $currentTeam->invites()->create($request->validated());

        return response()->json($response, Response::HTTP_CREATED);
    }
}
