<?php

namespace App\Http\Controllers\Gamification;

use App\Actions\Gamefication\ClaimVoiceXP;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RewardController extends Controller
{
    public function claimVoiceXP(int $discordId, ClaimVoiceXP $claimVoiceXP): JsonResponse
    {
        $claimVoiceXP->handle($discordId);

        return response()->json([], HttpResponse::HTTP_NO_CONTENT);
    }
}
