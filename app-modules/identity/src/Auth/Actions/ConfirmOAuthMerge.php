<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\PendingOAuthMergeDTO;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class ConfirmOAuthMerge
{
    public function __construct(
        private MergeAccountsAction $mergeAccounts,
        private PersistOAuthConnection $persistConnection,
    ) {}

    public function execute(User $currentUser, PendingOAuthMergeDTO $pending): ?User
    {
        return DB::transaction(function () use ($currentUser, $pending): ?User {
            $targetUser = User::query()
                ->lockForUpdate()
                ->find($pending->conflictingUserId);

            if (!$targetUser instanceof User || $targetUser->is($currentUser)) {
                return null;
            }

            $conflictingIdentity = ExternalIdentity::query()
                ->where('model_type', (new User)->getMorphClass())
                ->where('model_id', $targetUser->id)
                ->where('provider', $pending->provider)
                ->where('external_account_id', $pending->providerId)
                ->lockForUpdate()
                ->first();

            if (!$conflictingIdentity instanceof ExternalIdentity) {
                return null;
            }

            $this->mergeAccounts->execute($currentUser, $targetUser);

            $this->persistConnection->execute(
                owner: $targetUser,
                provider: $pending->provider,
                providerId: $pending->providerId,
                credentials: $pending->credentials,
                metadata: $pending->metadata,
                connectedBy: $targetUser->id,
            );

            return $targetUser->refresh();
        });
    }
}
