<?php

namespace Heart\Team\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Team\Infrastructure\Models\Team;
use Heart\Team\Presentation\Requests\CreateTeamRequest;
use Heart\Team\Presentation\Requests\UpdateTeamRequest;
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
            'role_id' => 1,
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

        return response()->json(['error' => 'Not found this team'], Response::HTTP_NOT_FOUND);
    }

    public function update(UpdateTeamRequest $request, string $team): JsonResponse
    {
        // validação de usuario autenticado TODO

        if ($currentTeam = Team::query()->find($team)) {
            return response()->json($currentTeam->update($request->validated()));
        }

        if ($currentTeam = Team::query()->where('slug', $team)->first()) {
            return response()->json($currentTeam->update($request->validated()));
        }

        return response()->json(['error' => 'Ǹot found this team'], Response::HTTP_NOT_FOUND);
    }

    public function destroy(string $team): \Illuminate\Http\Response|JsonResponse
    {
        // validação de usuario autenticado TODO

        if ($currentTeam = Team::query()->find($team)) {
            $currentTeam->delete();

            return response()->noContent();
        }

        if ($currentTeam = Team::query()->where('slug', $team)->first()) {
            $currentTeam->delete();

            return response()->noContent();
        }

        return response()->json(['error' => 'Not found this team'], Response::HTTP_NOT_FOUND);
    }
}
