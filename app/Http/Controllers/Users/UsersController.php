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
        $query = User::paginate(15);
        // Filters
        return $this->success($query);
    }

    /**
     * Cria um novo usuário
     *
     * Cria um novo usuário após ser acionado o comando /apresentar
     * @group Users
     * @bodyParam discord_id int required ID do usuário do Discord. Example: 204122995579551744
     * @responseFile 422 responses/Users/ValidationUserCreated.json
     * @responseFile 201 responses/Users/UserCreated.json
     */
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

        $payload = $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'email' => 'email',
            'name' => 'string',
            'nickname' => 'string',
            'git' => ['nullable', 'string', 'starts_with:github.com,https://github.com'],
            'about' => 'string',
            'linkedin' => ['nullable', 'string', 'starts_with:linkedin.com,https://linkedin.com'],
            'is_donator' => 'bool',
            'uf' => 'size:2',
            'birthday' => 'date_format:Y-m-d',
        ]);

        return response()->json($action->handle($discordId, $payload));
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
