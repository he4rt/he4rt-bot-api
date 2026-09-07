<?php

declare(strict_types=1);

return [
    'webhook_secret' => env('LIVE_WEBHOOK_SECRET', ''),
    'path' => 'live',
    'rtmp_server' => env('LIVE_RTMP_SERVER', 'rtmp://localhost:1935'),
    'ingest_user' => 'he4rt',
    'hls_url' => env('LIVE_HLS_URL', 'http://localhost:8888/live/index.m3u8'),
    'control_api_url' => env('LIVE_CONTROL_API_URL', 'http://localhost:9997'),
];
