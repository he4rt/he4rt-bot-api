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

    public function sendLevelupMessage()
    {
        return $this->client->po
    }
}
