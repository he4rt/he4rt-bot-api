<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class PendingOAuthMergeDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $conflictingUserId,
        public IdentityProvider $provider,
        public string $providerId,
        public ClientAccessManager $credentials,
        public array $metadata,
    ) {}
}
