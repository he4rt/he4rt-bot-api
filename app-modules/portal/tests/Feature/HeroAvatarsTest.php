<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Portal\Home\HeroSection;

use function Pest\Livewire\livewire;

/**
 * Cria um usuário "ativo" (identidade Discord com > 20 mensagens nos últimos
 * 30 dias) e vincula uma identidade GitHub a ele.
 */
function activeUserWithGithub(string $externalAccountId): User
{
    $user = User::factory()->create();

    $discordIdentity = ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
    ]);

    Message::factory()->count(21)->create([
        'external_identity_id' => $discordIdentity->id,
        'sent_at' => now()->subDay(),
    ]);

    ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => $externalAccountId,
    ]);

    return $user;
}

it('monta avatares a partir do CDN do GitHub usando o id numérico da conta', function (): void {
    activeUserWithGithub('583231');

    $avatars = livewire(HeroSection::class)->instance()->avatars();

    expect($avatars)->toContain('https://avatars.githubusercontent.com/u/583231?v=4');
});

it('nunca aponta os avatares para github.com (evita o Set-Cookie cross-site)', function (): void {
    activeUserWithGithub('583231');

    $avatars = livewire(HeroSection::class)->instance()->avatars();

    expect($avatars)->not->toBeEmpty()
        ->and(collect($avatars)->every(fn (string $url): bool => !str_contains($url, 'github.com/')))
        ->toBeTrue();
});

it('ignora identidades GitHub cujo external_account_id não é numérico', function (): void {
    activeUserWithGithub('not-a-numeric-id');

    $avatars = livewire(HeroSection::class)->instance()->avatars();

    expect($avatars)->toBeEmpty();
});
