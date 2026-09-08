<?php

declare(strict_types=1);

return [
    'short_link_status' => [
        'active' => [
            'label' => 'Active',
            'description' => 'Redirects normally, and every visit is recorded as a click',
        ],
        'expired' => [
            'label' => 'Expired',
            'description' => 'Past `expires_at` — serves the link unavailable page',
        ],
        'disabled' => [
            'label' => 'Disabled',
            'description' => 'Manually switched off or deleted — the slug stays reserved forever',
        ],
    ],
];
