<?php

namespace App\Http\Controllers\Events;

use App\Actions\Event\Badge\CreateBadge;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BadgesController extends Controller
{
    public function postBadge(Request $request, CreateBadge $action): JsonResponse
    {
        $payload = $this->validate($request, [
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'image_url' => ['url', 'required'],
            'redeem_code' => ['required', 'unique:badges'],
            'active' => ['required', 'bool'],
        ]);

        return response()->json(
            $action->handle($payload),
            Response::HTTP_CREATED
        );
    }
}
