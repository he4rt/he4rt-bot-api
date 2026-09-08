<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Contents\Articles\Actions\UpsertArticle;
use He4rt\Contents\Articles\DTOs\ArticleDTO;
use He4rt\Contents\Articles\DTOs\ArticleEngagementDTO;
use He4rt\Contents\Articles\Events\ArticlePublished;
use He4rt\Contents\Articles\Models\Article;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event;

/**
 * @param  array<string, mixed>  $overrides
 */
function makeArticleDto(array $overrides = []): ArticleDTO
{
    return new ArticleDTO(
        externalId: $overrides['externalId'] ?? '123',
        authorHandle: $overrides['authorHandle'] ?? 'johndoe',
        title: $overrides['title'] ?? 'A great article',
        url: $overrides['url'] ?? 'https://dev.to/johndoe/a-great-article',
        publishedAt: $overrides['publishedAt'] ?? CarbonImmutable::now()->subDays(1),
        description: $overrides['description'] ?? 'A short description',
        thumbnailUrl: $overrides['thumbnailUrl'] ?? 'https://dev.to/thumb.png',
        canonicalUrl: $overrides['canonicalUrl'] ?? null,
        readingTimeMinutes: $overrides['readingTimeMinutes'] ?? 5,
        bodyMarkdown: $overrides['bodyMarkdown'] ?? null,
        bodyHtml: $overrides['bodyHtml'] ?? null,
        sourceEditedAt: $overrides['sourceEditedAt'] ?? null,
        engagement: array_key_exists('engagement', $overrides) ? $overrides['engagement'] : new ArticleEngagementDTO(reactions: 10, comments: 2),
        tags: $overrides['tags'] ?? ['php', 'laravel'],
        detailHydrated: $overrides['detailHydrated'] ?? false,
    );
}

test('orphan article is persisted without author and without emitting event', function (): void {
    Event::fake([ArticlePublished::class]);

    $entry = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto());

    expect($entry->author_id)->toBeNull()
        ->and($entry->author_handle)->toBe('johndoe')
        ->and($entry->contentable)->toBeInstanceOf(Article::class);

    Event::assertNotDispatched(ArticlePublished::class);
});

test('create with a resolved author emits ArticlePublished', function (): void {
    Event::fake([ArticlePublished::class]);

    $user = User::factory()->create();
    ExternalIdentity::factory()->create([
        'model_type' => 'user',
        'model_id' => $user->id,
        'provider' => IdentityProvider::DevTo,
        'metadata' => ['username' => 'johndoe'],
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);

    $entry = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto());

    expect($entry->author_id)->toBe($user->id);

    Event::assertDispatched(fn (ArticlePublished $event): bool => $event->entry->id === $entry->id);
});

test('re-sync with a shallow dto does not erase previously hydrated body and metrics', function (): void {
    $hydratedDto = makeArticleDto([
        'bodyMarkdown' => '# Hello',
        'bodyHtml' => '<h1>Hello</h1>',
        'sourceEditedAt' => CarbonImmutable::now()->subDays(2),
        'engagement' => new ArticleEngagementDTO(reactions: 10, comments: 2, saves: 4),
        'detailHydrated' => true,
    ]);

    $entry = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, $hydratedDto);
    $entry->refresh();

    expect($entry->contentable->body_markdown)->toBe('# Hello')
        ->and($entry->saves_count)->toBe(4);

    $shallowDto = makeArticleDto([
        'engagement' => new ArticleEngagementDTO(reactions: 20, comments: 5),
        'detailHydrated' => false,
    ]);

    $reSynced = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, $shallowDto);
    $reSynced->refresh();

    expect($reSynced->contentable->body_markdown)->toBe('# Hello')
        ->and($reSynced->contentable->body_html)->toBe('<h1>Hello</h1>')
        ->and($reSynced->contentable->source_edited_at)->not->toBeNull()
        ->and($reSynced->saves_count)->toBe(4)
        ->and($reSynced->reactions_count)->toBe(20)
        ->and($reSynced->comments_count)->toBe(5);
});

test('a second hydrated update overwrites the body and metrics', function (): void {
    $entry = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto([
        'bodyMarkdown' => '# Old',
        'detailHydrated' => true,
    ]));

    $updated = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto([
        'bodyMarkdown' => '# New',
        'engagement' => new ArticleEngagementDTO(reactions: 1, comments: 1, saves: 9),
        'detailHydrated' => true,
    ]));

    expect($updated->id)->toBe($entry->id)
        ->and($updated->contentable->body_markdown)->toBe('# New')
        ->and($updated->saves_count)->toBe(9);
});

test('author_id never regresses to null once resolved', function (): void {
    Event::fake([ArticlePublished::class]);

    $user = User::factory()->create();
    ExternalIdentity::factory()->create([
        'model_type' => 'user',
        'model_id' => $user->id,
        'provider' => IdentityProvider::DevTo,
        'metadata' => ['username' => 'johndoe'],
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);

    $entry = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto());
    expect($entry->author_id)->toBe($user->id);

    // Simulate the identity becoming unresolvable on a later re-sync (e.g. disconnected).
    ExternalIdentity::query()->where('model_id', $user->id)->update(['disconnected_at' => now()]);

    $reSynced = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto());

    expect($reSynced->author_id)->toBe($user->id);
});

test('upsert is idempotent by provider and external_id', function (): void {
    resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto());
    resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto());

    expect(ContentEntry::query()->count())->toBe(1);
});

test('null engagement means the provider does not measure it, distinct from a measured zero', function (): void {
    $notMeasured = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto([
        'externalId' => '1',
        'engagement' => null,
    ]));

    expect($notMeasured->reactions_count)->toBeNull()
        ->and($notMeasured->comments_count)->toBeNull();

    $measuredZero = resolve(UpsertArticle::class)->execute(ContentProvider::DevTo, makeArticleDto([
        'externalId' => '2',
        'engagement' => new ArticleEngagementDTO(reactions: 0, comments: 0),
    ]));

    expect($measuredZero->reactions_count)->toBe(0)
        ->and($measuredZero->comments_count)->toBe(0);
});
