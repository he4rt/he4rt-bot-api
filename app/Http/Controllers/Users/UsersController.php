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

    public function postUser(Request $request)
    {
        $this->validate($request, [
            'discord_id' => 'required|unique:users|numeric',
        ]);
        $result = $this->repository->create($request->input('discord_id'));

        return $this->success($result);

    }

    public function getUser(Request $request, string $discordId)
    {
        $request->merge(['discord_id' => $discordId]);

        $this->validate($request, [
            'discord_id' => 'required|exists:users'
        ]);

        $result = $this->repository->findById(
            $request->input('discord_id'),
            $request->input('includes') ?? []
        );

        return $this->success($result);
    }


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


    public function deleteUser(string $discordId)
    {
        return $this->success($this->repository->delete($discordId));
    }

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
