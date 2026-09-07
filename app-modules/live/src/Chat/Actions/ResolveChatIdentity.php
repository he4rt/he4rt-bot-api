<?php

declare(strict_types=1);

namespace He4rt\Live\Chat\Actions;

use He4rt\Identity\ExternalIdentity\Actions\ResolveExternalIdentity;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

/** Garante a identidade sintética He4rtLives do usuário (find-or-create). */
final readonly class ResolveChatIdentity
{
    public function __construct(private ResolveExternalIdentity $resolveExternalIdentity) {}

    public function execute(User $user): ExternalIdentity
    {
        $identity = $this->resolveExternalIdentity->handle(ResolveUserProviderDTO::make([
            'provider' => IdentityProvider::He4rtLives,
            'external_account_id' => $user->id,
            'model_type' => $user->getMorphClass(),
            'username' => $user->username,
        ]));

        if ($identity->model_id !== $user->id) {
            $identity->update(['model_id' => $user->id]);
        }

        return $identity;
    }
}
