<?php

namespace Heart\Character\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Character\Domain\Actions\PaginateCharacters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CharactersController extends Controller
{
    public function getCharacters(PaginateCharacters $action): JsonResponse
    {
        return response()->json($action->handle());
    }

    public function getCharacter(): JsonResponse
    {
        return response()->json();
    }

    public function putCharacter(): JsonResponse
    {
        return response()->json();
    }

    public function postDailyBonus(): Response
    {
        return response()->noContent();
    }
}
