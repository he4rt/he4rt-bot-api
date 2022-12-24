<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Repositories\Gamification\RankingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    /**
     * @var RankingRepository
     */
    private $repository;

    public function __construct(RankingRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getGeneralLevelRanking(Request $request): JsonResponse
    {
        $result = $this->repository->rankingByLevel($request->all());
        return response()->json($result);
    }

    public function getGeneralMessageRanking(Request $request): JsonResponse
    {
        $result = $this->repository->rankingByMessages($request->all());
        return response()->json($result);
    }
}
