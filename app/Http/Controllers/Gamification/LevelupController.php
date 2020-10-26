<?php


namespace App\Http\Controllers\Gamification;


use App\Http\Controllers\Controller;
use App\Repositories\Gamification\LevelupRepository;
use Illuminate\Http\Request;

class LevelupController extends Controller
{
    /**
     * @var LevelupRepository
     */
    private $repository;

    public function __construct(LevelupRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Post(
     *     path="/bot/gamification/levelup",
     *     summary="Gamification Level Up",
     *     operationId="PostLevelUp",
     *     tags={"gamification-bot"},
     *     @OA\Parameter(
     *         name="discord_id",
     *         in="query",
     *         description="ID do Discord do usuário",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="donator",
     *         in="query",
     *         description="Se o usuário é um apoiador ou não (apoia-se)",
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
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */

    public function postLevelUp(Request $request)
    {
        $this->validate($request,[
            'discord_id' => 'required|exists:users',
            'message' => 'required'
        ]);

        $result = $this->repository->handle(
            $request->input('discord_id'),
            $request->input('donator'),
            $request->input('message')
        );

        return response()->json($result);
    }

}
