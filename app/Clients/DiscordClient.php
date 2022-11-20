<?php

namespace App\Clients;

use GuzzleHttp\Client;

class DiscordClient
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getUser(string $discordId): array
    {
        $headers = [
            'Authorization' => 'Bot ' . config('he4rt.discord.token'),
            'X-Ratelimit-Precision' => 'millisecond',
            'User-Agent' => 'DiscordBot (https://github.com/discord-php/DiscordPHP-HTTP, 9.1.6)',
        ];

        $uri = 'https://discord.com/api/v9/users/' . $discordId;
        $response = $this->client->get($uri, ['headers' => $headers]);

        return json_decode($response->getBody(), true);
    }

}
