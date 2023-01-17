<?php

namespace Heart\Integrations\Twitch\OAuth\Infrastructure;

use GuzzleHttp\Client;
use Heart\Authentication\OAuth\Domain\DTO\OAuthAccessDTO;
use Heart\Integrations\Twitch\OAuth\Domain\DTO\TwitchOAuthAccessDTO;
use Heart\Integrations\Twitch\OAuth\Domain\DTO\TwitchOAuthDTO;
use Heart\Integrations\Twitch\OAuth\Domain\TwitchOAuthService;

class TwitchOAuthClient implements TwitchOAuthService
{
    public function __construct(private readonly Client $client)
    {
    }

    public function redirectUrl(): string
    {
        return sprintf(
            'https://id.twitch.tv/oauth2/authorize?client_id=%s&redirect_uri=%s&response_type=code&scope=%s',
            config('kingdom.integrations.twitch.client_id'),
            config('kingdom.integrations.twitch.redirect_uri'),
            config('kingdom.integrations.twitch.scopes')
        );
    }

    public function auth(string $code): TwitchOAuthAccessDTO
    {
        $uri = "https://id.twitch.tv/oauth2/token";
        $response = $this->client->request('POST', $uri, [
            'form_params' => [
                'client_id' => config('kingdom.integrations.twitch.client_id'),
                'client_secret' => config('kingdom.integrations.twitch.client_secret'),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('kingdom.integrations.twitch.redirect_uri')
            ]
        ]);

        return TwitchOAuthAccessDTO::make(
            json_decode($response->getBody()->getContents(), true)
        );
    }

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): TwitchOAuthDTO
    {
        $uri = "https://api.twitch.tv/helix/users";
        $response = $this->client->request('GET', $uri, [
            'headers' => [
                'Client-ID' => config('kingdom.integrations.twitch.client_id'),
                'Authorization' => 'Bearer ' . $credentials->accessToken,
            ]
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);
        return TwitchOAuthDTO::make($credentials, $payload);
    }
}
