<?php


namespace App\Repositories;


use App\Contracts\OAuthServiceContract;
use App\Models\User;
use App\Service\DiscordAuthService;
use Illuminate\Support\Facades\Auth;

class AuthRepository
{
    public function authenticateUser(string $provider, string $code)
    {
        try {
            $authService = $this->getService($provider);
            $authData = $authService->auth($code);


            $response = $authService->getAuthenticatedUser($authData['access_token']);
            $authUser = $this->findOrCreate($provider, $response);

            return $authUser;

        } catch (\Exception $exception) {
            return false;
        }
    }

    public function findOrCreate(string $provider, $providerData): User
    {
        $auth = User::where($provider . "_id", $providerData['id'])->first();
        if (!$auth) {
            return User::create([
                'nickname' => $providerData['username'],
                'email' => $providerData['email'],
                $provider . "_id" => $providerData['id'],
            ]);
        }

        if (empty($auth->email)) {
            $auth->update([
                'email' => $providerData['email']
            ]);
            return $auth;
        }

        if ($auth->{$provider . "_id"} == $providerData['id']) {
            return $auth;
        }

        throw new \Exception('deu ruim');
    }

    private function getService(string $provider): OAuthServiceContract
    {
        if ($provider === "discord") {
            return new DiscordAuthService();
        } else {
            throw new \Exception('não existe esse provider');
        }
    }
}
