<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Contents\Articles\Events\ArticlePublished;
use He4rt\Contents\Database\Factories\ContentEntryFactory;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

function authorWithDevtoIdentity(): User
{
    $user = User::factory()->create();

    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::DevTo,
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);

    return $user;
}

test('artigo de autor com identidade devto ativa vira contribuição', function (): void {
    $user = authorWithDevtoIdentity();

    $entry = ContentEntryFactory::new()->authoredBy($user)->create([
        'external_id' => '123',
    ]);

    event(new ArticlePublished($entry->fresh()));

    $interaction = Interaction::query()
        ->where('source_type', 'content_entry')
        ->where('source_id', $entry->id)
        ->first();

    expect($interaction)->not->toBeNull()
        ->and($interaction->external_ref)->toBe('devto:article:123')
        ->and($interaction->type)->toBe(ActivityType::Article)
        ->and($interaction->user_id)->toBe($user->id)
        ->and($interaction->isVisible())->toBeTrue();
});

test('autor sem identidade devto conectada é ignorado', function (): void {
    $user = User::factory()->create();

    $entry = ContentEntryFactory::new()->authoredBy($user)->create([
        'external_id' => '456',
    ]);

    event(new ArticlePublished($entry->fresh()));

    expect(Interaction::query()->where('source_id', $entry->id)->exists())->toBeFalse();
});

test('identidade desconectada não recebe a contribuição', function (): void {
    $user = authorWithDevtoIdentity();
    $user->providers()->update(['disconnected_at' => now()]);

    $entry = ContentEntryFactory::new()->authoredBy($user)->create([
        'external_id' => '999',
    ]);

    event(new ArticlePublished($entry->fresh()));

    expect(Interaction::query()->where('source_id', $entry->id)->exists())->toBeFalse();
});

test('evento disparado duas vezes não duplica', function (): void {
    $user = authorWithDevtoIdentity();

    $entry = ContentEntryFactory::new()->authoredBy($user)->create([
        'external_id' => '789',
    ]);

    $fresh = $entry->fresh();

    event(new ArticlePublished($fresh));
    event(new ArticlePublished($fresh));

    expect(Interaction::query()->where('external_ref', 'devto:article:789')->count())->toBe(1);
});
