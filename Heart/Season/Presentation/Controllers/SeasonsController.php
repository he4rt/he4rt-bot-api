<?php

namespace Heart\Season\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Season\Application\GetCurrentSeason;
use Heart\Season\Application\GetSeasons;
use Symfony\Component\HttpFoundation\JsonResponse;

class SeasonsController extends Controller
{
    public function getSeasons(GetSeasons $getSeasons): JsonResponse
    {
        return response()->json($getSeasons->handle());
    }

    public function getCurrent(GetCurrentSeason $currentSeason): JsonResponse
    {
        return response()->json($currentSeason->handle());
    }
}
