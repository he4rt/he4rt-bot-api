<?php

namespace Heart\Team\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Team\Infrastructure\Models\Team;
use Heart\Team\Presentation\Requests\CreateTeamRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TeamController extends Controller
{
    public function getTeams(): JsonResponse
    {
        return response()->json(Team::paginate());
    }

    public function postTeam(CreateTeamRequest $request): JsonResponse
    {
        $teamPayload = $request->validated();

        $teamPayload['slug'] = str($teamPayload['name'])->slug();
        $team = Team::query()->create($teamPayload);

        $team->members()->create([
            'member_id' => $team->leader_id,
            'role_id' => 1
        ]);

        return response()->json($team, Response::HTTP_CREATED);
    }

    public function getTeam(string $team): JsonResponse
    {
        if ($currentTeam = Team::query()->find($team)) {
            return response()->json($currentTeam);
        }

        if ($currentTeam = Team::query()->where('slug', $team)->first()) {
            return response()->json($currentTeam);
        }

        return response()->json(['error' => 'not found'], Response::HTTP_NOT_FOUND);
    }
}
