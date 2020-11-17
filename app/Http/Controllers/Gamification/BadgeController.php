<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Repositories\Gamification\BadgeRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    use ApiResponse;

    /**
     * @var BadgeRepository
     */
    private $repository;

    public function __construct(BadgeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getBadges(Request $request)
    {
        // TODO: checar se tem qualquer validação pra integrar
        $result = $this->repository->fetchBadges();
        return $this->success($result);
    }

    public function postBadge(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'image' => 'required|image'
        ]);

        $result = $this->repository->newBadge(
            $request->input('name'),
            $request->input('description'),
            $request->file('image')
        );

        return $this->success($result);
    }

    public function getBadge(Request $request, int $badgeId)
    {
        $request->merge(['badge_id' => $badgeId]);
        $this->validate($request, [
            'badge_id' => 'required|exists:badges,id'
        ]);
        $result = $this->repository->findBadge($badgeId);

        return $this->success($result);
    }

    public function deleteBadge(Request $request, int $badgeId)
    {
        $request->merge(['badge_id' => $badgeId]);
        $this->validate($request, [
            'badge_id' => 'required|exists:badges,id'
        ]);
        $this->repository->deleteBadge($badgeId);
        return $this->success();
    }
}
