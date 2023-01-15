<?php

namespace Heart\Character\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Character\Domain\Actions\ClaimDailyBonus;
use Heart\Character\Domain\Actions\FindCharacter;
use Heart\Character\Domain\Actions\PaginateCharacters;
use Heart\Character\Domain\Exceptions\CharacterException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CharactersController extends Controller
{
    public function getCharacters(PaginateCharacters $action): JsonResponse
    {
        return response()->json($action->handle());
    }

    public function getCharacter(string $providerId, FindCharacter $action): JsonResponse
    {
        return response()->json($action->handle($providerId));
    }

    public function postDailyBonus(string $characterId, ClaimDailyBonus $action): Response|JsonResponse
    {
        try {
            $action->handle($characterId);
            return response()->noContent();
        } catch (CharacterException $e) {
            return response()->json($e->getMessage(), $e->getCode());
        }
    }
}
