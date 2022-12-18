<?php

namespace App\Http\Controllers\Gamification;

use App\Actions\Gamefication\Season\CurrentSeason;
use App\Actions\Gamefication\Season\PaginateSeasons;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SeasonsController extends Controller
{
    public function getSeasons(PaginateSeasons $action): JsonResponse
    {
        return response()->json($action->handle());
    }

    public function getCurrentSeason(CurrentSeason $action)
    {
        return response()->json($action->handle());
    }
}
