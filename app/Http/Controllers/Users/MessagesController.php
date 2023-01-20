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
            'channel_id' => ['required','string'],
            'message_id' => ['required', 'string'],
            'message_content' => ['required', 'string'],
            'message_at' => ['required'],
        ]);

        $action->handle($discordId, $payload);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
