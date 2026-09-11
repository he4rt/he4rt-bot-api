<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Activity\Message\Contracts\MessageActivityAdapter;
use He4rt\Activity\Message\Data\MembershipEventData;
use He4rt\Activity\Message\Data\ReplyData;
use He4rt\Activity\Message\Data\ThreadData;
use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Message\Models\MessageAttachment;
use He4rt\Activity\Message\Models\MessageEmbed;
use He4rt\Activity\Message\Models\MessageMention;
use He4rt\Activity\Message\Models\MessageThread;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\Identity\DiscordIdentityMetadata;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final class ImportDiscordMessageAction
{
    /**
     * @param  array<string, string>  $replyCache  provider_message_id => message uuid
     */
    public function handle(DiscordMessageDTO $dto, ?string $cachedIdentityId = null, array $replyCache = []): Message
    {
        $identityId = $cachedIdentityId ?? $this->resolveAuthorIdentity($dto)->id;
        $adapter = IdentityProvider::Discord->getMessageAdapter();

        $payload = $dto->toDatabase([
            'external_identity_id' => $identityId,
            'obtained_experience' => 0,
            ...$this->extractProviderSignals($dto, $adapter),
            'reply_to_message_id' => $this->resolveReplyTargetId($dto, $adapter, $replyCache, allowDbLookup: true),
        ]);

        $providerMessageId = $payload['provider_message_id'] ?? null;
        unset($payload['provider_message_id']);

        $message = Message::query()->updateOrCreate(
            [
                'provider_message_id' => $providerMessageId,
            ],
            $payload,
        );

        if ($adapter instanceof MessageActivityAdapter) {
            $this->syncMentions($message, $dto, $adapter);
            $this->syncThread($message, $dto, $adapter);
            $this->syncAttachments($message, $dto, $adapter);
            $this->syncEmbeds($message, $dto, $adapter);
            $this->syncMembershipEvent($message, $dto, $adapter);
        }

        return $message;
    }

    /**
     * @param  list<DiscordMessageDTO>  $dtos
     * @param  array<string, string>  $identityCache
     * @param  array<string, string>  $replyCache
     * @return array<string, Message> discordMessageId => Message stub
     */
    public function handleBatch(array $dtos, array $identityCache, array $replyCache): array
    {
        $adapter = IdentityProvider::Discord->getMessageAdapter();
        $now = now();
        $rows = [];
        /** @var array<string, Message> */
        $stubs = [];

        foreach ($dtos as $dto) {
            $id = (string) Uuid::uuid4();
            $identityId = $identityCache[$dto->authorDiscordId] ?? $this->resolveAuthorIdentity($dto)->id;

            $row = $dto->toDatabase([
                'id' => $id,
                'external_identity_id' => $identityId,
                'obtained_experience' => 0,
                ...$this->extractProviderSignals($dto, $adapter),
                'reply_to_message_id' => $this->resolveReplyTargetId($dto, $adapter, $replyCache),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (is_array($row['metadata'])) {
                $row['metadata'] = json_encode($row['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $rows[] = $row;

            $stub = (new Message)->forceFill([
                'id' => $id,
                'provider_message_id' => $dto->discordMessageId,
                'external_identity_id' => $identityId,
            ]);
            $stub->exists = true;
            $stubs[$dto->discordMessageId] = $stub;
        }

        if ($rows !== []) {
            Message::query()->insert($rows);
        }

        if ($adapter instanceof MessageActivityAdapter) {
            foreach ($dtos as $dto) {
                $stub = $stubs[$dto->discordMessageId];
                $this->syncMentions($stub, $dto, $adapter);
                $this->syncThread($stub, $dto, $adapter);
                $this->syncAttachments($stub, $dto, $adapter);
                $this->syncEmbeds($stub, $dto, $adapter);
                $this->syncMembershipEvent($stub, $dto, $adapter);
            }
        }

        return $stubs;
    }

    /**
     * Pre-resolve Discord author identities in bulk. Returns a cache map
     * `discord_id => external_identity_id` ready to be passed to handle().
     *
     * @param  iterable<DiscordMessageDTO>  $dtos
     * @param  array<string, string>  $existingCache
     * @return array<string, string>
     */
    public function prewarm(iterable $dtos, array $existingCache = []): array
    {
        $newAuthors = [];
        foreach ($dtos as $dto) {
            if (isset($existingCache[$dto->authorDiscordId])) {
                continue;
            }

            if (isset($newAuthors[$dto->authorDiscordId])) {
                continue;
            }

            $newAuthors[$dto->authorDiscordId] = $dto;
        }

        if ($newAuthors === []) {
            return $existingCache;
        }

        /** @var array<string, string> $existing */
        $existing = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->whereIn('external_account_id', array_keys($newAuthors))
            ->pluck('id', 'external_account_id')
            ->all();

        $cache = $existingCache + $existing;

        foreach ($newAuthors as $discordId => $dto) {
            if (isset($cache[$discordId])) {
                continue;
            }

            $cache[$discordId] = $this->createIdentity($dto)->id;
        }

        return $cache;
    }

    /**
     * @param  iterable<DiscordMessageDTO>  $dtos
     * @return array<string, string> reply_to_provider_message_id => message uuid
     */
    public function prewarmReplyTargets(iterable $dtos): array
    {
        $adapter = IdentityProvider::Discord->getMessageAdapter();
        if (!$adapter instanceof MessageActivityAdapter) {
            return [];
        }

        $replyProviderIds = [];
        foreach ($dtos as $dto) {
            $reply = $adapter->extractReply($dto->metadata);
            if ($reply instanceof ReplyData && !isset($replyProviderIds[$reply->replyToProviderMessageId])) {
                $replyProviderIds[$reply->replyToProviderMessageId] = true;
            }
        }

        if ($replyProviderIds === []) {
            return [];
        }

        /** @var array<string, string> */
        return Message::query()
            ->whereIn('provider_message_id', array_keys($replyProviderIds))
            ->pluck('id', 'provider_message_id')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function extractProviderSignals(DiscordMessageDTO $dto, ?MessageActivityAdapter $adapter): array
    {
        if (!$adapter instanceof MessageActivityAdapter) {
            return [];
        }

        $raw = $dto->metadata;
        $reply = $adapter->extractReply($raw);
        $editedAt = $adapter->editedAt($raw);

        return [
            'kind' => $adapter->messageKind($raw),
            'raw_message_type' => $adapter->rawMessageType($raw),
            'source_kind' => $adapter->sourceKind($raw),
            'is_pinned' => $adapter->isPinned($raw),
            'mentions_everyone' => $adapter->mentionsEveryone($raw),
            'mention_role_count' => $adapter->mentionRoleCount($raw),
            'edited_at' => $editedAt !== null ? Date::parse($editedAt) : null,
            'reply_to_provider_message_id' => $reply?->replyToProviderMessageId,
        ];
    }

    /**
     * @param  array<string, string>  $replyCache
     */
    private function resolveReplyTargetId(
        DiscordMessageDTO $dto,
        ?MessageActivityAdapter $adapter,
        array $replyCache = [],
        bool $allowDbLookup = false,
    ): ?string {
        if (!$adapter instanceof MessageActivityAdapter) {
            return null;
        }

        $reply = $adapter->extractReply($dto->metadata);
        if (!$reply instanceof ReplyData) {
            return null;
        }

        if (isset($replyCache[$reply->replyToProviderMessageId])) {
            return $replyCache[$reply->replyToProviderMessageId];
        }

        if (!$allowDbLookup) {
            return null;
        }

        return Message::query()
            ->where('provider_message_id', $reply->replyToProviderMessageId)
            ->value('id');
    }

    private function syncMentions(
        Message $message,
        DiscordMessageDTO $dto,
        MessageActivityAdapter $adapter,
    ): void {
        $mentions = $adapter->extractMentions($dto->metadata);
        if ($mentions === []) {
            return;
        }

        $providerIds = array_map(
            static fn ($mention): string => $mention->mentionedProviderAccountId,
            $mentions,
        );

        $identityMap = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->whereIn('external_account_id', $providerIds)
            ->pluck('id', 'external_account_id')
            ->all();

        foreach ($mentions as $mention) {
            MessageMention::query()->updateOrCreate(
                [
                    'message_id' => $message->id,
                    'mentioned_provider_account_id' => $mention->mentionedProviderAccountId,
                ],
                [
                    'mentioned_identity_id' => $identityMap[$mention->mentionedProviderAccountId] ?? null,
                    'mentioned_username' => $mention->mentionedUsername,
                    'position' => $mention->position,
                ],
            );
        }
    }

    private function syncThread(
        Message $message,
        DiscordMessageDTO $dto,
        MessageActivityAdapter $adapter,
    ): void {
        $thread = $adapter->extractThread($dto->metadata);
        if (!$thread instanceof ThreadData) {
            return;
        }

        MessageThread::query()->updateOrCreate(
            [
                'provider_thread_id' => $thread->providerThreadId,
            ],
            [
                'message_id' => $message->id,
                'name' => $thread->name,
                'archived' => $thread->archived,
                'auto_archive_duration' => $thread->autoArchiveDuration,
            ],
        );
    }

    private function syncAttachments(
        Message $message,
        DiscordMessageDTO $dto,
        MessageActivityAdapter $adapter,
    ): void {
        $attachments = $adapter->extractAttachments($dto->metadata);
        if ($attachments === []) {
            return;
        }

        $rawAttachments = $dto->metadata['attachments'] ?? [];
        $providerIds = [];
        foreach ($rawAttachments as $index => $attachment) {
            $providerIds[$index] = isset($attachment['id']) ? (string) $attachment['id'] : null;
        }

        // Wipe+reinsert: Discord re-serves the whole attachment array every edit
        // and lacks a natural merge key for composite content_type/size changes.
        MessageAttachment::query()->where('message_id', $message->id)->delete();

        $rows = [];
        foreach ($attachments as $index => $attachment) {
            $rows[] = [
                'id' => (string) Uuid::uuid4(),
                'message_id' => $message->id,
                'provider_attachment_id' => $providerIds[$index] ?? null,
                'url' => $attachment->url,
                'filename' => $attachment->filename,
                'content_type' => $attachment->contentType,
                'size' => $attachment->size,
                'width' => $attachment->width,
                'height' => $attachment->height,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        MessageAttachment::query()->insert($rows);
    }

    private function syncEmbeds(
        Message $message,
        DiscordMessageDTO $dto,
        MessageActivityAdapter $adapter,
    ): void {
        $embeds = $adapter->extractEmbeds($dto->metadata);
        if ($embeds === []) {
            return;
        }

        // Same reasoning as attachments: Discord resends the full embed list on
        // every edit, with no stable per-embed identity in the payload.
        MessageEmbed::query()->where('message_id', $message->id)->delete();

        $rows = [];
        foreach ($embeds as $index => $embed) {
            $rows[] = [
                'id' => (string) Uuid::uuid4(),
                'message_id' => $message->id,
                'url' => $embed->url,
                'title' => $embed->title,
                'description' => $embed->description,
                'source_domain' => $embed->sourceDomain,
                'kind' => $embed->kind,
                'thumbnail_url' => $embed->thumbnailUrl,
                'raw' => json_encode($embed->raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'position' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        MessageEmbed::query()->insert($rows);
    }

    private function syncMembershipEvent(
        Message $message,
        DiscordMessageDTO $dto,
        MessageActivityAdapter $adapter,
    ): void {
        $event = $adapter->extractMembershipEvent($dto->metadata);
        if (!$event instanceof MembershipEventData) {
            return;
        }

        MembershipEvent::query()->updateOrCreate(
            [
                'provider_message_id' => $message->provider_message_id,
            ],
            [
                'external_identity_id' => $message->external_identity_id,
                'kind' => $event->kind,
                'occurred_at' => Date::parse($event->occurredAt),
                'metadata' => $event->metadata,
            ],
        );
    }

    private function resolveAuthorIdentity(DiscordMessageDTO $dto): ExternalIdentity
    {
        $identity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->authorDiscordId)
            ->first();

        if ($identity) {
            $metadata = DiscordIdentityMetadata::mergeMessage(
                $identity->metadata ?? [],
                $dto,
            )->toArray();

            if ($metadata !== ($identity->metadata ?? [])) {
                $identity->metadata = $metadata;
                $identity->save();
            }

            return $identity;
        }

        return $this->createIdentity($dto);
    }

    private function createIdentity(DiscordMessageDTO $dto): ExternalIdentity
    {
        $user = $this->resolveOrCreateUser($dto);

        return ExternalIdentity::query()->create([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $dto->authorDiscordId,
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'type' => IdentityProvider::Discord->getType(),
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => ClientAccessManager::make(),
            'metadata' => DiscordIdentityMetadata::fromMessage($dto)->toArray(),
        ]);
    }

    private function resolveOrCreateUser(DiscordMessageDTO $dto): User
    {
        $conflictingIds = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', '!=', $dto->authorDiscordId)
            ->where('model_type', (new User)->getMorphClass())
            ->pluck('model_id')
            ->all();

        $existing = User::query()
            ->whereIn('username', [$dto->authorUsername, $dto->authorUsername.'#0'])
            ->when($conflictingIds !== [], fn ($q) => $q->whereNotIn('id', $conflictingIds))
            ->orderByRaw('CASE WHEN username = ? THEN 0 ELSE 1 END', [$dto->authorUsername])
            ->first();

        if ($existing instanceof User) {
            return $existing;
        }

        $candidates = [
            $dto->authorName,
            $dto->authorUsername,
            $dto->authorUsername.'-'.$dto->authorDiscordId,
            $dto->authorDiscordId,
        ];

        $takenNames = User::query()
            ->whereIn('name', $candidates)
            ->pluck('name')
            ->flip()
            ->all();

        foreach ($candidates as $name) {
            if (isset($takenNames[$name])) {
                continue;
            }

            try {
                return DB::transaction(fn (): User => User::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'username' => $dto->authorUsername,
                    'name' => $name,
                    'is_donator' => false,
                ]));
            } catch (UniqueConstraintViolationException) {
                $raced = User::query()->where('username', $dto->authorUsername)->first();
                if ($raced instanceof User) {
                    return $raced;
                }
            }
        }

        throw new RuntimeException(sprintf(
            'All name candidates collided for Discord user %s (%s)',
            $dto->authorDiscordId,
            $dto->authorUsername,
        ));
    }
}
