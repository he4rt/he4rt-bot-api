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

    /**
     * @OA\Get(
     *     path="/badges",
     *     summary="Retorna a lista de badges paginada",
     *     operationId="GetBadges",
     *     tags={"badges"},
     *     security={{
     *          "api_key":{}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */

    public function getBadges(Request $request)
    {
        $result = $this->repository->fetchBadges();
        return $this->success($result);
    }

    /**
     * @OA\Post(
     *     path="/badges",
     *     summary="Cria uma nova insignia no sistema",
     *     operationId="PostBadges",
     *     tags={"badges"},
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      description="Nome da insignia",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="description",
     *                      description="Descrição da insignia",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="image",
     *                      description="Imagem da insignia",
     *                      type="file"
     *                   ),
     *               ),
     *           ),
     *     ),
     *     security={{
     *          "api_key":{}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/badges/{badgeId}",
     *     summary="Retorna uma badge especifica",
     *     operationId="GetBadge",
     *     tags={"badges"},
     *     @OA\Parameter(
     *         name="badgeId",
     *         in="query",
     *         description="ID da insignia",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     security={{
     *          "api_key":{}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */

    public function getBadge(Request $request, int $badgeId)
    {
        $request->merge(['badge_id' => $badgeId]);
        $this->validate($request, [
            'badge_id' => 'required|exists:badges,id'
        ]);
        $result = $this->repository->findBadge($badgeId);

        return $this->success($result);
    }

    /**
     * @OA\Delete(
     *     path="/badges/{badgeId}",
     *     summary="Deleta uma badge especifica",
     *     operationId="DeleteBadge",
     *     tags={"badges"},
     *     @OA\Parameter(
     *         name="badgeId",
     *         in="query",
     *         description="ID da insignia",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     security={{
     *          "api_key":{}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */
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
