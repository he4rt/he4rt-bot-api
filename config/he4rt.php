<?php

use Carbon\Carbon;

return [
    'season' => [
        'id' => env('SEASON_ID', 2),
        'minimum_level_for_retro' => env('SEASON_MIN_LEVEL', 3),
    ],
    'server_key' => env('BOT_SECRET', 'he4rt'),
    'discord' => [
        'token' => env('HE4RT_DISCORD_BOT_KEY'),
        'levelup_channel_id' => env('HE4RT_DISCORD_LEVELUP_CHANNEL', '552332704381927424'),
        'guild_id' => env('HE4RT_DISCORD_GUILD', '452926217558163456'),
    ],
    'seasons' => [
        [
            'name' => 'Legado 4Y',
            'description' => 'A primeira temporada que dura desde o inicio da He4rt Developers.',
            'starts_at' => Carbon::parse('2018-08-01'),
            'ends_at' => Carbon::parse('2022-12-31'),
            'messages_count' => 0,
            'participants_count' => 0,
            'meetings_count' => 0,
            'badges_count' => 0,
        ],
        [
            'name' => 'He4rt Shippuden',
            'description' => 'Segunda temporada chegando foda memo dps nois acha um nome melhor pra isso aqui.',
            'starts_at' => Carbon::parse('2023-01-01'),
            'ends_at' => Carbon::parse('2023-12-31'),
            'messages_count' => 0,
            'participants_count' => 0,
            'meetings_count' => 0,
            'badges_count' => 0,
        ],
    ],
];
