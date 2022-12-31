<?php

namespace App\Clients;

use GuzzleHttp\Client;

class DiscordClient
{
    private Client $client;

    const BATCH_MAX = 1000;

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

    public function getPaginatedGuildUsers(string $lastUserId = null)
    {
        $headers = [
            'Authorization' => 'Bot ' . config('he4rt.discord.token'),
            'X-Ratelimit-Precision' => 'millisecond',
            'User-Agent' => 'DiscordBot (https://github.com/discord-php/DiscordPHP-HTTP, 9.1.6)',
        ];
        $query = '';
        if (!empty($lastUserId)) {
            $query = '&after=' . $lastUserId;
        }
        $uri = sprintf(
            'https://discord.com/api/v9/guilds/%s/members?&limit=%s%s',
            config('he4rt.discord.guild_id'),
            self::BATCH_MAX,
            $query
        );
        $response = $this->client->get($uri, ['headers' => $headers]);

        return json_decode($response->getBody(), true);
    }
}
