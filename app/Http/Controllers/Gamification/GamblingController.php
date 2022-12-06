<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Repositories\Gamification\GamblingRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class GamblingController extends Controller
{
    use ApiResponse;

    private GamblingRepository $repository;

    public function __construct(GamblingRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Atualiza a economia do usuário
     *
     * Atualiza a economia do usuário
     * @group Gamification
     * @bodyParam discord_id int required ID do usuário do Discord. Example: 9
     * @bodyParam operation string required Add || Reduce. Example: operação teste
     * @bodyParam value float required Valor a ser mutado. Example: 10.50
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
