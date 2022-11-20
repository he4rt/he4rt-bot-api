<?php

namespace App\Http\Controllers\Users;

use App\Actions\Message\CreateMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MessagesController extends Controller
{
    public function postMessage(Request $request, string $discordId, CreateMessage $action)
    {
        $request->merge(['discord_id' => $discordId]);

        $payload = $this->validate($request, [
            'discord_id' => ['required','exists:users'],
            'message' => ['string', 'required'],
            'donator' => ['required','boolean']
        ]);

        $action->handle($discordId, $payload['message'], $payload['donator']);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
