<?php

declare(strict_types=1);

use He4rt\Contents\Articles\Actions\ReconcileOrphanEntries;
use He4rt\Contents\Articles\Events\ArticlePublished;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event;

test('adopts every orphan entry matching provider and handle, emitting one event per adopted entry', function (): void {
    Event::fake([ArticlePublished::class]);

    $user = User::factory()->create();

    $orphans = ContentEntry::factory()->count(3)->create([
        'provider' => ContentProvider::DevTo,
        'author_handle' => 'johndoe',
        'author_id' => null,
    ]);

    $unrelated = ContentEntry::factory()->create([
        'provider' => ContentProvider::DevTo,
        'author_handle' => 'someone-else',
        'author_id' => null,
    ]);

    $adopted = resolve(ReconcileOrphanEntries::class)->execute($user, ContentProvider::DevTo, 'johndoe');

    expect($adopted)->toBe(3);

    foreach ($orphans as $orphan) {
        expect($orphan->fresh()->author_id)->toBe($user->id);
    }

    expect($unrelated->fresh()->author_id)->toBeNull();

    Event::assertDispatched(ArticlePublished::class, 3);
});

test('handle ignores identities that are not attached to a User model', function (): void {
    Event::fake([ArticlePublished::class]);

    $identity = ExternalIdentity::factory()->morphFor(Character::class)->create([
        'provider' => IdentityProvider::DevTo,
        'metadata' => ['username' => 'johndoe'],
    ]);

    resolve(ReconcileOrphanEntries::class)->handle(new ExternalIdentityConnected($identity->fresh()));

    Event::assertNotDispatched(ArticlePublished::class);
});

test('handle ignores identities without a username in metadata', function (): void {
    Event::fake([ArticlePublished::class]);

    $user = User::factory()->create();
    $identity = ExternalIdentity::factory()->create([
        'model_type' => 'user',
        'model_id' => $user->id,
        'provider' => IdentityProvider::DevTo,
        'metadata' => [],
    ]);

    resolve(ReconcileOrphanEntries::class)->handle(new ExternalIdentityConnected($identity));

    Event::assertNotDispatched(ArticlePublished::class);
});

test('handle ignores providers without a content provider mapping', function (): void {
    Event::fake([ArticlePublished::class]);

    $user = User::factory()->create();
    $identity = ExternalIdentity::factory()->create([
        'model_type' => 'user',
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'metadata' => ['username' => 'johndoe'],
    ]);

    resolve(ReconcileOrphanEntries::class)->handle(new ExternalIdentityConnected($identity));

    Event::assertNotDispatched(ArticlePublished::class);
});

test('a second reconciliation pass adopts nothing', function (): void {
    $user = User::factory()->create();

    ContentEntry::factory()->create([
        'provider' => ContentProvider::DevTo,
        'author_handle' => 'johndoe',
        'author_id' => null,
    ]);

    $action = resolve(ReconcileOrphanEntries::class);

    expect($action->execute($user, ContentProvider::DevTo, 'johndoe'))->toBe(1)
        ->and($action->execute($user, ContentProvider::DevTo, 'johndoe'))->toBe(0);
});
