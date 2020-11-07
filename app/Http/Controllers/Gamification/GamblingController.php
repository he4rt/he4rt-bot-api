<?php


namespace App\Http\Controllers\Gamification;


use App\Http\Controllers\Controller;
use App\Repositories\Gamification\GamblingRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class GamblingController extends Controller
{
    use ApiResponse;

    /**
     * @var GamblingRepository
     */
    private $repository;

    public function __construct(GamblingRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * @OA\Put(
     *     path="/bot/gambling/money",
     *     summary="Atualiza a economia do usuário",
     *     operationId="PutGamblingMoney",
     *     tags={"gamification-bot"},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="Authorization: he4rt-{key}",
     *         required=false,
     *         @OA\Schema(
     *           type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="discord_id",
     *         in="query",
     *         description="ID do usuário do Discord",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="operation",
     *         in="query",
     *         description="Add || Reduce",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="value",
     *         in="query",
     *         description="Valor a ser mutado",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     security={{
     *          "api_key":{}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */
    public function putMoney(Request $request)
    {
        $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'operation' => 'required',
            'value' => 'required|numeric',
        ]);

        $data = $request->all();
        $result = $this->repository->updateMoney(
            $data['discord_id'],
            $data['operation'],
            $data['value']
        );

        return $this->success($result);
    }

}
