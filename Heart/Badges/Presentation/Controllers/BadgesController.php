<?php

namespace Heart\Badges\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Heart\Badges\Application\CreateBadge;
use Heart\Badges\Domain\Actions\DeleteBadge;
use Heart\Badges\Presentation\Requests\CreateBadgeRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BadgesController extends Controller
{
    public function getBadges()
    {
    }

    public function postBadge(
        CreateBadgeRequest $request,
        CreateBadge $persistBadge
    ): JsonResponse {
        return response()->json(
            $persistBadge->handle($request->validated()),
            Response::HTTP_CREATED
        );
    }

    public function deleteBadge(string $badgeId, DeleteBadge $deleteBadge): Response
    {
        $deleteBadge->handle($badgeId);

        return response()->noContent();
    }
}
