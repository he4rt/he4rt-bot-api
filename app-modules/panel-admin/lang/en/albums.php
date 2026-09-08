<?php

declare(strict_types=1);

return [
    'label' => 'Album',
    'plural' => 'Photo albums',

    'columns' => [
        'title' => 'Title',
        'slug' => 'Slug',
        'event' => 'Event',
        'location' => 'Location',
        'happened_at' => 'Happened on',
        'description' => 'Description',
        'published_at' => 'Published at',
        'photos' => 'Photos',
        'photos_count' => 'Photos',
        'created_at' => 'Created at',
    ],

    'form' => [
        'helpers' => [
            'event' => 'Optional. Picking an event fills location and date when they are empty.',
            'published_at' => 'Leave empty to keep the album as a draft, hidden from the portal.',
            'photos' => 'Up to :max_files photos per upload, :max_mb MB each. JPG, PNG or WEBP.',
        ],
    ],

    'filters' => [
        'published' => 'Published',
        'drafts' => 'Drafts',
    ],

    'photos' => [
        'title' => 'Photos',
        'empty' => 'Upload photos through the "Photos" field in the form above.',
        'columns' => [
            'preview' => 'Preview',
            'highlight' => 'Highlight',
            'caption' => 'Caption',
            'size' => 'Size',
            'uploaded_at' => 'Uploaded at',
        ],
    ],
];
