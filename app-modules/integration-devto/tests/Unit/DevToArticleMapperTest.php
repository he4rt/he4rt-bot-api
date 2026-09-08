<?php

declare(strict_types=1);

use He4rt\IntegrationDevTo\Articles\DevToArticleMapper;

beforeEach(function (): void {
    $this->mapper = new DevToArticleMapper();
});

test('maps a listing payload field by field', function (): void {
    $dto = $this->mapper->fromListing([
        'id' => 123,
        'user' => ['username' => 'danielhe4rt'],
        'title' => 'My Article',
        'url' => 'https://dev.to/danielhe4rt/my-article',
        'published_at' => '2026-08-01T12:00:00Z',
        'description' => 'A short description',
        'cover_image' => 'https://dev.to/cover.png',
        'canonical_url' => 'https://dev.to/canonical',
        'reading_time_minutes' => 5,
        'edited_at' => '2026-08-02T12:00:00Z',
        'tag_list' => ['php', 'laravel'],
        'public_reactions_count' => 42,
        'comments_count' => 3,
    ]);

    expect($dto)->not->toBeNull()
        ->and($dto->externalId)->toBe('123')
        ->and($dto->authorHandle)->toBe('danielhe4rt')
        ->and($dto->title)->toBe('My Article')
        ->and($dto->url)->toBe('https://dev.to/danielhe4rt/my-article')
        ->and($dto->publishedAt->format('Y-m-d'))->toBe('2026-08-01')
        ->and($dto->description)->toBe('A short description')
        ->and($dto->thumbnailUrl)->toBe('https://dev.to/cover.png')
        ->and($dto->canonicalUrl)->toBe('https://dev.to/canonical')
        ->and($dto->readingTimeMinutes)->toBe(5)
        ->and($dto->sourceEditedAt?->format('Y-m-d'))->toBe('2026-08-02')
        ->and($dto->tags)->toBe(['php', 'laravel'])
        ->and($dto->engagement?->reactions)->toBe(42)
        ->and($dto->engagement?->comments)->toBe(3)
        ->and($dto->engagement?->saves)->toBeNull()
        ->and($dto->bodyMarkdown)->toBeNull()
        ->and($dto->bodyHtml)->toBeNull()
        ->and($dto->detailHydrated)->toBeFalse();
});

test('returns null when the mandatory core is missing', function (array $payload): void {
    expect($this->mapper->fromListing($payload))->toBeNull();
})->with([
    'missing id' => [['user' => ['username' => 'x'], 'title' => 't', 'url' => 'u', 'published_at' => 'p']],
    'missing user.username' => [['id' => 1, 'title' => 't', 'url' => 'u', 'published_at' => 'p']],
    'missing title' => [['id' => 1, 'user' => ['username' => 'x'], 'url' => 'u', 'published_at' => 'p']],
    'missing url' => [['id' => 1, 'user' => ['username' => 'x'], 'title' => 't', 'published_at' => 'p']],
    'missing published_at' => [['id' => 1, 'user' => ['username' => 'x'], 'title' => 't', 'url' => 'u']],
    'empty title' => [['id' => 1, 'user' => ['username' => 'x'], 'title' => '', 'url' => 'u', 'published_at' => 'p']],
]);

test('normalizes tag_list as an array on the listing', function (): void {
    $dto = $this->mapper->fromListing([
        'id' => 1,
        'user' => ['username' => 'x'],
        'title' => 't',
        'url' => 'u',
        'published_at' => '2026-08-01T00:00:00Z',
        'tag_list' => ['  php ', '', 'laravel'],
    ]);

    expect($dto?->tags)->toBe(['php', 'laravel']);
});

test('normalizes tag_list as a comma separated string on the detail, falling back to tags array', function (): void {
    $shallow = $this->mapper->fromListing([
        'id' => 1,
        'user' => ['username' => 'x'],
        'title' => 't',
        'url' => 'u',
        'published_at' => '2026-08-01T00:00:00Z',
        'tag_list' => ['old'],
    ]);

    $dto = $this->mapper->fromDetail([
        'id' => 1,
        'user' => ['username' => 'x'],
        'title' => 't',
        'url' => 'u',
        'published_at' => '2026-08-01T00:00:00Z',
        'tag_list' => 'php, laravel , webdev',
        'tags' => ['php', 'laravel', 'webdev'],
    ], $shallow);

    expect($dto->tags)->toBe(['php', 'laravel', 'webdev']);
});

test('fromDetail sets detailHydrated and reading_list_count as saves', function (): void {
    $shallow = $this->mapper->fromListing([
        'id' => 1,
        'user' => ['username' => 'x'],
        'title' => 't',
        'url' => 'u',
        'published_at' => '2026-08-01T00:00:00Z',
    ]);

    $dto = $this->mapper->fromDetail([
        'id' => 1,
        'user' => ['username' => 'x'],
        'title' => 't',
        'url' => 'u',
        'published_at' => '2026-08-01T00:00:00Z',
        'body_markdown' => '# Hello',
        'body_html' => '<h1>Hello</h1>',
        'reading_list_count' => 7,
        'public_reactions_count' => 0,
        'comments_count' => 0,
    ], $shallow);

    expect($dto->detailHydrated)->toBeTrue()
        ->and($dto->bodyMarkdown)->toBe('# Hello')
        ->and($dto->bodyHtml)->toBe('<h1>Hello</h1>')
        ->and($dto->engagement?->saves)->toBe(7)
        ->and($dto->engagement?->reactions)->toBe(0)
        ->and($dto->engagement?->comments)->toBe(0);
});

test('preserves null vs zero on engagement counters', function (): void {
    $dto = $this->mapper->fromListing([
        'id' => 1,
        'user' => ['username' => 'x'],
        'title' => 't',
        'url' => 'u',
        'published_at' => '2026-08-01T00:00:00Z',
        'public_reactions_count' => 0,
    ]);

    expect($dto?->engagement?->reactions)->toBe(0)
        ->and($dto?->engagement?->comments)->toBeNull()
        ->and($dto?->engagement?->saves)->toBeNull();
});
