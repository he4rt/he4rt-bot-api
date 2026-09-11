<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\DTOs\ConnectedAccountDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;
use He4rt\IntegrationDiscord\Identity\DiscordIdentityMetadata;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

final class ImportDiscordProfileAction
{
    public function handle(DiscordProfileDTO $dto): ExternalIdentity
    {
        $user = $this->resolveUser($dto);

        $discordIdentity = ExternalIdentity::query()->firstOrNew([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $dto->discordId,
        ]);

        if (!$discordIdentity->exists) {
            $discordIdentity->forceFill([
                'model_type' => (new User)->getMorphClass(),
                'model_id' => $user->id,
                'type' => IdentityProvider::Discord->getType(),
                'credentials_type' => CredentialsType::OAuth2,
                'credentials' => ClientAccessManager::make(),
            ]);
        }

        $discordIdentity->metadata = DiscordIdentityMetadata::mergeProfile(
            $discordIdentity->metadata ?? [],
            $dto,
        )->toArray();
        $discordIdentity->save();

        foreach ($dto->connectedAccounts as $account) {
            $this->upsertConnectedAccount($account, $user, $dto);
        }

        return $discordIdentity;
    }

    private function resolveUser(DiscordProfileDTO $dto): User
    {
        $identity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->discordId)
            ->first();

        if ($identity instanceof ExternalIdentity) {
            $owner = $identity->user;
            if ($owner instanceof User) {
                return $this->syncUserAttributes($owner, $dto);
            }
        }

        $user = User::query()->where('username', $dto->username)->first();
        if ($user instanceof User) {
            return $user;
        }

        $name = User::query()->where('name', $dto->name)->exists()
            ? $dto->username
            : $dto->name;

        return User::query()->create([
            'id' => Uuid::uuid7(),
            'username' => $dto->username,
            'name' => $name,
            'is_donator' => false,
        ]);
    }

    private function syncUserAttributes(User $user, DiscordProfileDTO $dto): User
    {
        $changes = [];

        $usernameChanged = $user->username !== $dto->username
            && !User::query()
                ->where('username', $dto->username)
                ->where('id', '!=', $user->id)
                ->exists();

        if ($usernameChanged) {
            $changes['username'] = $dto->username;
        }

        if ($user->name === $user->username && $dto->name !== $dto->username) {
            $changes['name'] = $dto->name;
        }

        if ($changes !== []) {
            $user->update($changes);
        }

        return $user;
    }

    private function upsertConnectedAccount(
        ConnectedAccountDTO $account,
        User $user,
        DiscordProfileDTO $dto,
    ): void {
        $existing = ExternalIdentity::query()
            ->where('provider', $account->provider)
            ->where('external_account_id', $account->externalAccountId)
            ->first();

        if ($existing instanceof ExternalIdentity && (string) $existing->model_id !== (string) $user->id) {
            Log::warning('discord-profile-import skipped connected account: belongs to other user', [
                'discord_id' => $dto->discordId,
                'discord_username' => $dto->username,
                'provider' => $account->provider->value,
                'external_account_id' => $account->externalAccountId,
                'owner_user_id' => $existing->model_id,
                'tried_user_id' => $user->id,
            ]);

            return;
        }

        $identity = $existing ?? new ExternalIdentity;

        if (!$identity->exists) {
            $identity->forceFill([
                'provider' => $account->provider,
                'external_account_id' => $account->externalAccountId,
                'model_type' => (new User)->getMorphClass(),
                'model_id' => $user->id,
                'type' => $account->provider->getType(),
                'credentials_type' => CredentialsType::OAuth2,
                'credentials' => ClientAccessManager::make(),
            ]);
        }

        $identity->metadata = array_replace($identity->metadata ?? [], $account->metadata);
        $identity->save();
    }
}
