<?php


namespace App\Http\Controllers\Users;


use App\Exceptions\DailyRewardException;
use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Repositories\Users\UsersRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{

    use ApiResponse;

    /**
     * @var User
     */
    private $model;
    /**
     * @var UsersRepository
     */
    private $repository;

    public function __construct(User $model, UsersRepository $repository)
    {
        $this->model = $model;
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Lista todos os usuários",
     *     operationId="GetUsers",
     *     tags={"users"},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="Authorization: he4rt-{key}",
     *         required=false,
     *         @OA\Schema(
     *           type="string"
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

    public function getUsers(Request $request)
    {
        $query = $this->model->paginate(15);
        // Filters
        return $this->success($query);
    }

    /**
     * @OA\Post(
     *     path="/users",
     *     summary="Cria um novo usuário",
     *     operationId="PostUser",
     *     tags={"users"},
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
     *     security={{
     *          "api_key":{}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */
    public function postUser(Request $request)
    {
        $this->validate($request, [
            'discord_id' => 'required|unique:users|numeric',
        ]);
        $result = $this->repository->create($request->input('discord_id'));

        return $this->success($result);

    }

    /**
     * @OA\Get(
     *     path="/users/{discordId}",
     *     summary="Mostra as informações de um usuário",
     *     operationId="GetUser",
     *     tags={"users"},
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
     *         name="discordId",
     *         in="path",
     *         description="ID do usuário do Discord",
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

    public function getUser(Request $request, string $discordId)
    {
        $request->merge(['discord_id' => $discordId]);

        $this->validate($request, [
            'discord_id' => 'required|exists:users'
        ]);

        $result = $this->repository->findById(
            $request->input('discord_id'),
            $request->input('includes')
        );

        return $this->success($result);
    }

    /**
     * @OA\Put(
     *     path="/users/{discordId}",
     *     summary="Altera um usuário",
     *     operationId="PutUser",
     *     tags={"users"},
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
     *         name="discordId",
     *         in="path",
     *         description="ID do usuário do Discord",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nome da pessoa",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="nickname",
     *         in="query",
     *         description="Apelido da pessoa",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="git",
     *         in="query",
     *         description="Link do git",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="about",
     *         in="query",
     *         description="Informações pessoais do usuário",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     ),
     *     security={{
     *          "api_key":{}
     *     }},
     * )
     */
    public function putUser(Request $request, string $discordId)
    {
        $request->merge(['discord_id' => $discordId]);
        $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'name' => 'string',
            'nickname' => 'string',
            'git' => 'string',
            'about' => 'string'
        ]);

        $result = $this->repository->update(
            $request->input('discord_id'),
            $request->only(['name', 'nickname', 'git', 'about'])
        );

        return $this->success($result);
    }

    /**
     * @OA\Delete(
     *     path="/users/{discordId}",
     *     summary="Apaga um usuário",
     *     operationId="DeleteUser",
     *     tags={"users"},
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
     *         name="discordId",
     *         in="path",
     *         description="ID do usuário do Discord",
     *         required=true,
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

    public function deleteUser(string $discordId)
    {
        return $this->success($this->repository->delete($discordId));
    }

    /**
     * @OA\Post(
     *     path="/users/daily",
     *     summary="Gerador de hCoins diário",
     *     operationId="postUserDailyCoins",
     *     tags={"users"},
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
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */

    public function postDaily(Request $request)
    {
        $this->validate($request, [
            'discord_id' => 'required|exists:users',
        ]);

        try {
            $isDonator = $request->has('donator') ? $request->input('donator') : false;
            $result = $this->repository->dailyPoints(
                $request->input('discord_id'),
                $isDonator
            );

            return $this->success($result);
        } catch (DailyRewardException $exception) {
            return $this->unprocessable([
                'message' => 'Command already used today',
                'time' => $exception->getMessage()
            ]);
        }
    }

}
