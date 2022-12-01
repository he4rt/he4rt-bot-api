<?php


use Carbon\Carbon;

return [
    'season' => env('APP_SEASON', 1),
    'server_key' => env('BOT_SECRET', 'he4rt'),
    'discord' => [
        'token' => env('HE4RT_DISCORD_BOT_KEY'),
        'levelup_channel_id' => env('HE4RT_DISCORD_LEVELUP_CHANNEL', '552332704381927424'),
        'guild_id' => env('HE4RT_DISCORD_GUILD', '1042817315156262912')
    ],
    'seasons' => [
        [
            'name' => 'Legado 4Y',
            'description' => 'A primeira temporada que dura desde o inicio da He4rt Developers.',
            'start_at' => Carbon::parse('2018-08-01'),
            'ends_at' => Carbon::parse('2022-12-31'),
            'messages_count' => 0,
            'participants_count' => 0
        ],
        [
            'name' => 'Um não tão novo RECOMEÇO',
            'description' => 'Segunda temporada chegando foda memo dps nois acha um nome melhor pra isso aqui.',
            'start_at' => Carbon::parse('2023-01-01'),
            'ends_at' => Carbon::parse('2023-12-31'),
            'messages_count' => 0,
            'participants_count' => 0
        ]
    ]
];
