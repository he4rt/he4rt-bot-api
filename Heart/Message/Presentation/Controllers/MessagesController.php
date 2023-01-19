<?php

namespace Heart\Message\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Message\Application\NewMessage;
use Heart\Message\Presentation\Request\CreateMessageRequest;
use Illuminate\Http\Response;

class MessagesController extends Controller
{
    public function postMessage(
        CreateMessageRequest $request,
        string $provider,
        NewMessage $action,
    ): Response {
        $action->handle($request->validated());
        return response()->noContent();
    }
}
