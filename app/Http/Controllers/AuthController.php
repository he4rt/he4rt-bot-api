<?php


namespace App\Http\Controllers;


use App\Repositories\AuthRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    /**
     * @var AuthRepository
     */
    private $repository;

    public function __construct(AuthRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Post(
     *     path="/auth/oauth/{provider}",
     *     summary="Autenticação de usuário",
     *     operationId="AuthLogin",
     *     tags={"auth"},
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="Token de autorização da Twitch",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */

    public function authenticate(Request $request, string $provider)
    {
        $auth = $this->repository->authenticateUser($provider, $request->get('code'));
        $response = $this->passportAuth($auth->email, $auth->discord_id);

        return response()->json($response['message'], $response['status']);
    }

    public function getPassportPasswordKey()
    {
        return \DB::table('oauth_clients')
            ->where('password_client', '=', true)
            ->select('id', 'secret', 'redirect')
            ->first();
    }

    public function passportAuth($username, $password)
    {
        $keys = $this->getPassportPasswordKey();
        try {
            $tokenRequest = Request::create('/oauth/token', 'POST', [
                'grant_type' => 'password',
                'client_id' => $keys->id,
                'client_secret' => $keys->secret,
                'username' => $username,
                'password' => $password
            ]);

//            dd($keys->id, $keys->secret);
            $response = app()->handle($tokenRequest);

            if ($response->getStatusCode() == 200) {
                $result = json_decode($response->getContent(), true);
                $result['expires_in'] = Carbon::now()->addSeconds($result['expires_in'])->timestamp;

                return [
                    'status' => 200,
                    'message' => $result
                ];
            }
            return [
                'status' => 401, 'message' => ['Unauthorized']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 401, 'message' => ['Unauthorized']
            ];
        }

    }
}
