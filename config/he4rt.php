<?php


return [
    'season' => env('APP_SEASON', 1),
    'server_key' => env('BOT_SECRET', 'he4rt'),
    'discord' => [
        'token' => env('HE4RT_DISCORD_BOT_KEY'),
        'levelup_channel_id' => env('HE4RT_DISCORD_LEVELUP_CHANNEL', '552332704381927424'),
        'guild_id' => env('HE4RT_DISCORD_GUILD', '1042817315156262912')
    ]
];
