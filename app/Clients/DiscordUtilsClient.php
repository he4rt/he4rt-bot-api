<?php

namespace App\Clients;

use GuzzleHttp\Client;

class DiscordUtilsClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'Authorization' => config('credentials.authorization')
            ]
        ]);
    }

    public function getProfile($userId)
    {
        $uri = "https://discord.com/api/v9/users/$userId/profile";
        $response = $this->client->get($uri);

        return json_decode($response->getBody(), true);
    }

    public function getGuild($guildId)
    {
        $uri = 'https://discord.com/api/v9/guilds/' . $guildId;
        $response = $this->client->get($uri);

        return json_decode($response->getBody(), true);
    }

    public function getGuildChannels($guildId)
    {
        $uri = 'https://discord.com/api/v9/guilds/' . $guildId . '/channels';
        $response = $this->client->get($uri);

        return json_decode($response->getBody(), true);
    }


    public function retrieveChannelMessages($channelId, $lastMessageId, $limit = 50)
    {
        $uri = "https://discord.com/api/v9/channels/$channelId/messages?";
        $query = http_build_query([
            'before' => $lastMessageId,
            'limit' => $limit
        ]);

        $response = $this->client->get($uri . $query);

        return json_decode($response->getBody(), true);
    }
}
