<?php

namespace Heart\Team\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Team\Domain\Enums\InviteAnswerEnum;
use Heart\Team\Infrastructure\Models\Invite;
use Heart\Team\Infrastructure\Models\Team;
use Heart\Team\Presentation\Requests\CreateInviteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamInvitationController extends Controller
{
    public function postInvite(CreateInviteRequest $request, string $team): JsonResponse
    {
        $currentTeam = Team::query()->find($team)
            ?? Team::query()->where('slug', $team)->first();

        if (! $currentTeam) {
            return response()->json(['error' => 'Not found this team'], Response::HTTP_NOT_FOUND);
        }

        if (! $currentTeam->isLeadership($request->input('invited_by'))) {
            return response()->json(['error' => 'you are not on the leadership of this team'], Response::HTTP_FORBIDDEN);
        }

        $response = $currentTeam->invites()->create($request->validated());

        return response()->json($response, Response::HTTP_CREATED);
    }

    public function handleInvite(Request $request, string $inviteId): JsonResponse
    {
        $invite = Invite::query()
            ->where('id', $inviteId)
            ->first();

        if (! $invite) {
            return response()->json('Not found this invite', Response::HTTP_NOT_FOUND);
        }

        $inviteStatus = InviteAnswerEnum::from($request->input('answer'));
        dump($invite);
        match ($inviteStatus) {
            InviteAnswerEnum::ACCEPT => $invite->accept(),
            InviteAnswerEnum::DECLINE => $invite->delete(),
        };

        return response()->json(['message' => $inviteStatus->getMessage()], $inviteStatus->getCode());
    }
}
