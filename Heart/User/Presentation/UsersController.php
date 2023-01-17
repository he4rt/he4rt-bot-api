<?php

namespace Heart\User\Presentation;

use App\Http\Controllers\Controller;
use Heart\User\Application\GetUser;
use Heart\User\Application\GetUsersPaginated;
use Heart\User\Domain\Exceptions\UserEntityException;
use Illuminate\Http\JsonResponse;

class UsersController extends Controller
{
    public function getUsers(GetUsersPaginated $getUsers): JsonResponse
    {
        return response()->json($getUsers->handle());
    }

    public function getUser(int $id, GetUser $getUser): JsonResponse
    {
        try {
            return response()->json($getUser->handle($id));
        } catch (UserEntityException $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                $e->getCode()
            );
        }
    }
}
