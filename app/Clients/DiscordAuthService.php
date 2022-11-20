<?php

namespace App\Clients;

use App\Contracts\OAuthServiceContract;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DiscordAuthService implements OAuthServiceContract
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://discord.com/api/v6',
            'timeout' => 5.0,
        ]);
    }

    public function auth(string $code): array
    {
        $url = "https://discord.com/api/v6/oauth2/token";
        try {
            $response = $this->client->request('POST', $url, [
                'form_params' => [
                    'client_id' => env('DISCORD_CLIENT_ID'),
                    'client_secret' => env('DISCORD_CLIENT_SECRET'),
                    'code' => $code,
                    'scope' => 'email identify',
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => env('DISCORD_REDIRECT_URI'),
                ],
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $exception) {
            return ["deu merda: " . $exception->getMessage()];
        }
    }

    public function getAuthenticatedUser(string $token): array
    {
        $url = "https://discord.com/api/v6/users/@me";
        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $exception) {
            return ["deu merda: " . $exception->getMessage()];
        }
    }
}
