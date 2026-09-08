<?php

declare(strict_types=1);

use He4rt\Contents\Enums\ContentProvider;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationDevTo\Articles\DevToArticleMapper;
use He4rt\IntegrationDevTo\Articles\DevToArticleProvider;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

test('provider() reports the devto content provider', function (): void {
    expect(resolve(DevToArticleProvider::class)->provider())->toBe(ContentProvider::DevTo);
});

test('fetchFromSource paginates the org listing and stops once a page has fewer than 30 items', function (): void {
    $firstPage = array_map(fn (int $i): array => [
        'id' => $i,
        'user' => ['username' => 'author'],
        'title' => "Article {$i}",
        'url' => "https://dev.to/author/article-{$i}",
        'published_at' => '2026-08-01T00:00:00Z',
    ], range(1, 30));

    $secondPage = [[
        'id' => 31,
        'user' => ['username' => 'author'],
        'title' => 'Article 31',
        'url' => 'https://dev.to/author/article-31',
        'published_at' => '2026-08-01T00:00:00Z',
    ]];

    Http::fakeSequence('dev.to/api/articles*')
        ->push($firstPage)
        ->push($secondPage);

    $dtos = iterator_to_array(resolve(DevToArticleProvider::class)->fetchFromSource());

    expect($dtos)->toHaveCount(31);

    Http::assertSentCount(2);
});

test('fetchForIdentity sends the api-key header from the decrypted credentials', function (): void {
    Http::fake([
        'dev.to/api/articles/me/published*' => Http::response([[
            'id' => 1,
            'user' => ['username' => 'danielhe4rt'],
            'title' => 'My Article',
            'url' => 'https://dev.to/danielhe4rt/my-article',
            'published_at' => '2026-08-01T00:00:00Z',
        ]]),
    ]);

    $identity = ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::DevTo,
        'credentials_type' => CredentialsType::ApiKey,
        'credentials' => ClientAccessManager::make(apiKey: Crypt::encrypt('secret-key')),
    ]);

    $dtos = iterator_to_array(resolve(DevToArticleProvider::class)->fetchForIdentity($identity));

    expect($dtos)->toHaveCount(1)
        ->and($dtos[0]->externalId)->toBe('1');

    Http::assertSent(fn ($request): bool => $request->hasHeader('api-key', 'secret-key'));
});

test('fetchForIdentity yields nothing and sends no request when the identity has no api key', function (): void {
    Http::fake();

    $identity = ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::DevTo,
        'credentials_type' => CredentialsType::OAuth2,
        'credentials' => ClientAccessManager::make(accessToken: Crypt::encrypt('token')),
    ]);

    $dtos = iterator_to_array(resolve(DevToArticleProvider::class)->fetchForIdentity($identity));

    expect($dtos)->toBeEmpty();

    Http::assertNothingSent();
});

test('fetchDetail returns a hydrated dto', function (): void {
    Http::fake([
        'dev.to/api/articles/1' => Http::response([
            'id' => 1,
            'user' => ['username' => 'danielhe4rt'],
            'title' => 'My Article',
            'url' => 'https://dev.to/danielhe4rt/my-article',
            'published_at' => '2026-08-01T00:00:00Z',
            'body_markdown' => '# Hello',
            'body_html' => '<h1>Hello</h1>',
            'reading_list_count' => 3,
        ]),
    ]);

    $shallowDto = new DevToArticleMapper()->fromListing([
        'id' => 1,
        'user' => ['username' => 'danielhe4rt'],
        'title' => 'My Article',
        'url' => 'https://dev.to/danielhe4rt/my-article',
        'published_at' => '2026-08-01T00:00:00Z',
    ]);

    $hydrated = resolve(DevToArticleProvider::class)->fetchDetail($shallowDto);

    expect($hydrated->detailHydrated)->toBeTrue()
        ->and($hydrated->bodyMarkdown)->toBe('# Hello')
        ->and($hydrated->bodyHtml)->toBe('<h1>Hello</h1>')
        ->and($hydrated->engagement?->saves)->toBe(3);
});
