<?php

namespace Heart\Message\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Message\Application\NewMessage;
use Heart\Message\Application\NewVoiceMessage;
use Heart\Message\Presentation\Request\CreateMessageRequest;
use Heart\Message\Presentation\Request\CreateVoiceMessageRequest;
use Illuminate\Http\Response;

class MessagesController extends Controller
{
    public function postMessage(
        CreateMessageRequest $request,
        string $provider,
        NewMessage $newMessage,
    ): Response {
        $newMessage->persist($request->validated());

        return response()->noContent();
    }

    public function postVoiceMessage(
        CreateVoiceMessageRequest $request,
        string $provider,
        NewVoiceMessage $voiceMessage,
    ): Response {
        $voiceMessage->persist($request->validated());

        return response()->noContent();
    }
}
