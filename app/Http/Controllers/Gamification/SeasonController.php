<?php


namespace App\Http\Controllers\Gamification;


use App\Http\Controllers\Controller;
use App\Repositories\Gamification\SeasonRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SeasonController extends Controller
{

    use ApiResponse;

    /**
     * @var SeasonRepository
     */
    private $repository;

    public function __construct(SeasonRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getSeasons(Request $request)
    {
        $result = $this->repository->paginateSeasons();
        return $this->success($result);
    }

    public function getActiveSeason(Request $request)
    {
        $result = $this->repository->fetchActiveSeason();
        return $this->success($result);
    }

    public function postSeason(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'duration' => 'required|date'
        ]);

        $result = $this->repository->createNewSeason(
            $request->input('name'),
            $request->input('duration')
        );

        return $this->success($result);
    }

    public function getSeason(Request $request, int $seasonId)
    {
        $request->merge(['season_id' => $seasonId]);
        $this->validate($request, [
            'season_id' => 'required|exists:seasons,id'
        ]);

        $result = $this->repository->fetchSeason($seasonId);
        return $this->success($result);
    }

    public function putSeason(Request $request, int $seasonId)
    {
        $request->merge(['season_id' => $seasonId]);
        $this->validate($request, [
            'season_id' => 'required|exists:seasons,id',
            'name' => 'string',
            'duration' => 'date'
        ]);

        $result = $this->repository->updateSeason(
            $seasonId,
            $request->input('name'),
            $request->input('duration'),
        );
        return $this->success($result);
    }

    public function deleteSeason(int $seasonId)
    {
        return $this->repository->deleteSeason($seasonId);
    }
}
