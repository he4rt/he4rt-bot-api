<?php

namespace App\Http\Controllers\Users;

use App\Actions\User\CreateUser;
use App\Actions\User\DailyUserPoints;
use App\Actions\User\DeleteUser;
use App\Actions\User\GetUser;
use App\Actions\User\UpdateUser;
use App\Exceptions\DailyRewardException;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Repositories\Users\UsersRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersController extends Controller
{
    use ApiResponse;

    private $model;

    private $repository;

    public function __construct(User $model, UsersRepository $repository)
    {
        $this->model = $model;
        $this->repository = $repository;
    }

    public function getUsers(Request $request)
    {
        $query = $this->model->paginate(15);
        // Filters
        return $this->success($query);
    }

    public function postUser(Request $request, CreateUser $action): JsonResponse
    {
        $payload = $this->validate($request, ['discord_id' => 'required|unique:users|numeric']);
        return response()->json($action->handle($payload['discord_id']), Response::HTTP_CREATED);
    }

    public function getUser(Request $request, string $discordId, GetUser $action): JsonResponse
    {
        $request->merge(['discord_id' => $discordId]);
        $this->validate($request, ['discord_id' => 'required|exists:users']);

        return response()->json($action->handle($discordId));
    }


    /** @throws UserException */
    public function putUser(Request $request, string $discordId, UpdateUser $action): JsonResponse
    {
        $request->merge(['discord_id' => $discordId]);

        $validated = $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'email' => 'email',
            'name' => 'string',
            'nickname' => 'string',
            'git' => 'string',
            'about' => 'string',
            'linkedin' => 'string',
            'is_donator' => 'bool',
            'uf' => 'size:2'
        ]);

        return response()->json($action->handle($discordId, $validated));
    }


    public function deleteUser(string $discordId, DeleteUser $action): JsonResponse
    {
        $action->handle($discordId);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function postDaily(
        Request $request,
        string $discordId,
        DailyUserPoints $action
    ): JsonResponse {
        $request->merge(['discord_id' => $discordId]);
        $this->validate($request, ['discord_id' => 'required|exists:users']);

        try {
            return response()->json($action->handle($discordId));
        } catch (DailyRewardException $e) {
            return response()->json($e->getMessage(), $e->getCode());
        }
    }
}
