<?php

namespace App\Http\Controllers\Events;

use App\Actions\Event\Badge\ClaimBadge;
use App\Actions\Event\Badge\CreateBadge;
use App\Exceptions\BadgeException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BadgesController extends Controller
{
    public function postBadge(Request $request, CreateBadge $action): JsonResponse
    {
        $payload = $this->validate($request, [
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'image_url' => ['url', 'required'],
            'redeem_code' => ['required', 'unique:badges'],
            'active' => ['required', 'bool'],
        ]);

        return response()->json(
            $action->handle($payload),
            Response::HTTP_CREATED
        );
    }

    public function postClaimBadge(Request $request, string $discordId, ClaimBadge $action): JsonResponse
    {
        $request->merge(['discord_id' => $discordId]);

        $payload = $this->validate($request, [
            'discord_id' => ['required', 'exists:users'],
            'redeem_code' => ['string', 'required', 'exists:badges'],
        ]);
        try {
            return response()->json(
                ['message' => $action->handle($discordId, $payload['redeem_code'])],
                Response::HTTP_CREATED
            );
        } catch (BadgeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
