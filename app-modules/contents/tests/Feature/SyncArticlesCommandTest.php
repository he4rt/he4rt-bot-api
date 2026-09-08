<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Contents\Articles\ArticleProviderRegistry;
use He4rt\Contents\Articles\Contracts\ArticleProvider;
use He4rt\Contents\Articles\Contracts\DiscoversBySource;
use He4rt\Contents\Articles\Contracts\HydratesDetail;
use He4rt\Contents\Articles\DTOs\ArticleDTO;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Contents\Models\ContentEntry;
use Illuminate\Support\Facades\Artisan;

function fakeDto(string $externalId, ?DateTimeImmutable $sourceEditedAt = null): ArticleDTO
{
    return new ArticleDTO(
        externalId: $externalId,
        authorHandle: 'someone',
        title: 'Title '.$externalId,
        url: 'https://dev.to/someone/'.$externalId,
        publishedAt: CarbonImmutable::now()->subDays(1),
        sourceEditedAt: $sourceEditedAt,
    );
}

test('only invokes capabilities the provider actually implements', function (): void {
    $sourceProvider = new class implements ArticleProvider, DiscoversBySource
    {
        public bool $sourceCalled = false;

        public function provider(): ContentProvider
        {
            return ContentProvider::DevTo;
        }

        public function fetchFromSource(): iterable
        {
            $this->sourceCalled = true;

            yield fakeDto('src-1');
        }
    };

    resolve(ArticleProviderRegistry::class)->register($sourceProvider);

    Artisan::call('contents:sync-articles');

    expect($sourceProvider->sourceCalled)->toBeTrue()
        ->and(ContentEntry::query()->where('external_id', 'src-1')->exists())->toBeTrue();
});

test('an article without a changed edited_at does not trigger fetchDetail', function (): void {
    $editedAt = CarbonImmutable::now()->subDays(3);

    $callCount = 0;

    $provider = new class($editedAt, $callCount) implements ArticleProvider, DiscoversBySource, HydratesDetail
    {
        public int $detailCalls = 0;

        public function __construct(private readonly DateTimeImmutable $editedAt) {}

        public function provider(): ContentProvider
        {
            return ContentProvider::DevTo;
        }

        public function fetchFromSource(): iterable
        {
            yield fakeDto('stable-1', $this->editedAt);
        }

        public function fetchDetail(ArticleDTO $shallow): ArticleDTO
        {
            $this->detailCalls++;

            return new ArticleDTO(
                externalId: $shallow->externalId,
                authorHandle: $shallow->authorHandle,
                title: $shallow->title,
                url: $shallow->url,
                publishedAt: $shallow->publishedAt,
                sourceEditedAt: $shallow->sourceEditedAt,
                detailHydrated: true,
            );
        }
    };

    $registry = resolve(ArticleProviderRegistry::class);
    $registry->register($provider);

    Artisan::call('contents:sync-articles');
    expect($provider->detailCalls)->toBe(1);

    Artisan::call('contents:sync-articles');
    expect($provider->detailCalls)->toBe(1);
});

test('an exception from one provider does not stop the command nor delete existing entries', function (): void {
    ContentEntry::factory()->create([
        'provider' => ContentProvider::DevTo,
        'external_id' => 'keep-me',
    ]);

    $brokenProvider = new class implements ArticleProvider, DiscoversBySource
    {
        public function provider(): ContentProvider
        {
            return ContentProvider::DevTo;
        }

        public function fetchFromSource(): iterable
        {
            throw new RuntimeException('provider exploded');
        }
    };

    resolve(ArticleProviderRegistry::class)->register($brokenProvider);

    $exitCode = Artisan::call('contents:sync-articles');

    expect($exitCode)->toBe(0)
        ->and(ContentEntry::query()->where('external_id', 'keep-me')->exists())->toBeTrue();
});
