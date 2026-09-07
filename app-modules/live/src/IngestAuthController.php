<?php

declare(strict_types=1);

namespace He4rt\Live;

use He4rt\Live\Actions\AuthorizeMediaServerAction;
use He4rt\Live\DTOs\IngestAuthPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class IngestAuthController
{
    public function __invoke(Request $request, AuthorizeMediaServerAction $action): Response
    {
        $payload = IngestAuthPayload::fromArray($request->json()->all());

        abort_unless($action->execute($payload), Response::HTTP_FORBIDDEN);

        return response()->noContent();
    }
}
