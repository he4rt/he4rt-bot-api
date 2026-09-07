<?php

declare(strict_types=1);

namespace He4rt\Live;

use He4rt\Live\Actions\MarkLiveOnline;
use He4rt\Live\Models\Live;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Recebe os hooks runOnOnline/runOnOffline do mediamtx. */
final class IngestWebhookController
{
    public function __invoke(Request $request, MarkLiveOnline $markOnline): Response
    {
        $secret = config()->string('live.webhook_secret');

        abort_unless(
            $secret !== '' && hash_equals($secret, (string) $request->header('X-Live-Webhook-Secret')),
            Response::HTTP_FORBIDDEN,
        );

        if ($request->json('event') === 'online') {
            $live = Live::query()->current()->first();

            if ($live !== null) {
                $markOnline->execute($live);
            }
        }

        return response()->noContent();
    }
}
