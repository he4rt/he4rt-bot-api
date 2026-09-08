<?php

declare(strict_types=1);

return [
    'navigation' => [
        'cluster' => 'Marketing',
        'cluster_breadcrumb' => 'Marketing',
        'back_to_admin' => 'Back to Admin',
        'meeting_showcase' => 'Meeting Showcase',
        'discord_dashboard' => 'Discord Dashboard',
        'short_links' => 'Short links',
    ],

    'short_links' => [
        'label' => 'Short link',
        'plural' => 'Short links',

        'sections' => [
            'link' => 'Link',
            'lifecycle' => 'Lifecycle',
            'utm' => 'UTM parameters',
            'history' => 'Destination history',
            'about' => 'About this link',
            'numbers' => 'Numbers',
            'filter' => 'Filter',
            'destination_history' => 'Destination history',
            'destination_history_hint' => 'The slug never changes — the destination does.',
        ],

        'fields' => [
            'nickname' => 'Nickname',
            'slug' => 'Slug',
            'short_url' => 'Short URL',
            'destination_url' => 'Destination',
            'tags' => 'Tags',
            'active' => 'Active',
            'expires_at' => 'Expires at',
            'status' => 'Status',
            'clicks' => 'Clicks',
            'total_clicks' => 'Total clicks',
            'created_at' => 'Created at',
            'created_by' => 'Created by',
            'valid_from' => 'Valid from',
            'valid_until' => 'Valid until',
            'valid_since' => 'Current since :date',
            'changed_by' => 'Changed by',
            'utm_source' => 'utm_source',
            'utm_medium' => 'utm_medium',
            'utm_campaign' => 'utm_campaign',
            'utm_term' => 'utm_term',
            'utm_content' => 'utm_content',
        ],

        'helpers' => [
            'nickname' => 'The readable half of the URL. A random 5-character suffix is appended automatically — "discord" becomes "discord-a3f9k".',
            'slug' => 'The slug is immutable and never reused: a URL already printed or pasted somewhere never moves.',
            'destination_url' => 'Where the link points right now. Changing the destination keeps the short URL and records the change in the history.',
            'tags' => 'Free-form labels used to group and filter links (e.g. comunidade, hacktoberfest).',
            'expires_at' => 'After this date the link stops redirecting. Leave blank to never expire.',
            'utm' => 'Appended to the destination on redirect, so the destination site analytics also sees where the visit came from.',
        ],

        'placeholders' => [
            'none' => '—',
            'current' => 'Current',
            'no_referer' => 'Direct',
            'unknown' => 'Unknown',
        ],

        'filters' => [
            'status' => 'Status',
            'tag' => 'Tag',
        ],

        'actions' => [
            'edit_destination' => 'Edit destination',
            'disable' => [
                'label' => 'Disable',
                'heading' => 'Disable this link?',
                'body' => 'The short URL stops redirecting immediately. Nothing is deleted — the history and the clicks stay where they are.',
            ],
            'enable' => [
                'label' => 'Enable',
                'heading' => 'Re-enable this link?',
                'body' => 'The short URL starts redirecting to the current destination again.',
            ],
            'copy_url' => [
                'label' => 'Copy short URL',
                'copied' => 'Short URL copied!',
            ],
        ],

        'notifications' => [
            'disabled' => [
                'title' => 'Link disabled',
            ],
            'enabled' => [
                'title' => 'Link re-enabled',
            ],
            'created' => [
                'title' => 'Short link created',
                'body' => 'The short URL is :url',
            ],
            'invalid_destination' => [
                'title' => 'Destination rejected',
            ],
        ],

        'stats' => [
            'clicks' => 'Clicks',
            'peak' => 'Busiest day',
            'top_source' => 'Top source',
            'humans_only' => 'humans only',
            'including_bots' => 'humans + bots',
            'never_expires' => 'Never expires',
            'no_clicks_yet' => 'No clicks yet',
            'share' => ':clicks clicks · :share',
        ],

        'table' => [
            'clicks_description' => ':total total · :bots from bots',
        ],

        'widgets' => [
            'include_bots' => [
                'label' => 'Include bots',
                'helper' => 'Preview bots inflate the count without anyone clicking.',
            ],

            'clicks_over_time' => [
                'heading' => 'Clicks per day',
                'dataset' => 'Clicks',
                'empty' => 'No clicks recorded in this period.',
                'ranges' => [
                    '7' => 'Last 7 days',
                    '30' => 'Last 30 days',
                    '90' => 'Last 90 days',
                ],
            ],

            'top_referers' => [
                'heading' => 'Where they came from',
                'origin' => 'Origin',
                'clicks' => 'Clicks',
                'share' => 'Share',
                'dimension' => 'Dimension',
                'empty_heading' => 'No clicks yet',
                'empty_description' => 'As soon as someone opens the short URL, the origin of the visit shows up here.',
                'dimensions' => [
                    'referer' => 'Referer',
                    'utm_source' => 'UTM source',
                    'country_code' => 'Country',
                ],
            ],

            'recent_clicks' => [
                'heading' => 'Recent clicks',
                'human' => 'human',
                'bot' => 'bot',
                'empty_heading' => 'No clicks yet',
                'empty_description' => 'Every hit on the short URL shows up here as a row.',
                'columns' => [
                    'clicked_at' => 'When',
                    'device' => 'Device / browser / OS',
                    'origin' => 'Origin',
                ],
            ],
            'device_breakdown' => [
                'heading' => 'Devices',
                'empty' => 'No clicks recorded yet.',
                'dimensions' => [
                    'device_type' => 'Device',
                    'browser' => 'Browser',
                    'os' => 'OS',
                ],
            ],
        ],
    ],
];
