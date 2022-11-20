<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Repositories\Gamification\RankingRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    use ApiResponse;

    /**
     * @var RankingRepository
     */
    private $repository;

    public function __construct(RankingRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve Ranking by Level/Exp
     * @param Request $request
     * @author danielhe4rt - hey@danielheart.dev
     * @param Request $request
     * @return JsonResponse
     * @OA\Get(
     *     path="/ranking/general",
     *     summary="Retorna o ranking de Level/exp global",
     *     operationId="getRankingGeneral",
     *     tags={"ranking"},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="Nem existe ainda mas vo deixa documentado",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */

    public function getGeneralLevelRanking(Request $request)
    {
        $result = $this->repository->rankingByLevel($request->all());
        return $this->success($result);
    }

    /**
     * Quick description about the function
     * @author danielhe4rt - hey@danielheart.dev
     * @param Request $request
     *
     * @return JsonResponse
     * @OA\Get(
     *     path="/ranking/messages",
     *     summary="Retorna o ranking de Mensagens",
     *     operationId="getRankingMessages",
     *     tags={"ranking"},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="week = ultima semana | month = ultimo mes",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */
    public function getGeneralMessageRanking(Request $request)
    {
        $result = $this->repository->rankingByMessages($request->all());
        return $this->success($result);
    }
}
