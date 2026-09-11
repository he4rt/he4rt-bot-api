<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Identity;

use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;

final readonly class DiscordIdentityMetadata
{
    /** @param array<string, mixed> $payload */
    private function __construct(private array $payload) {}

    /**
     * @param  array<string, mixed>  $current
     */
    public static function mergeProfile(array $current, DiscordProfileDTO $profile): self
    {
        /** @var array<string, mixed> $user */
        $user = is_array($profile->metadata['user'] ?? null)
            ? $profile->metadata['user']
            : [];

        $publicFields = [
            'username' => $profile->username,
            'global_name' => $profile->name,
        ];

        if (array_key_exists('avatar', $user)) {
            $publicFields['avatar'] = $user['avatar'];
        }

        return new self(array_replace(
            $current,
            $profile->metadata,
            $publicFields,
        ));
    }

    public static function fromMessage(DiscordMessageDTO $message): self
    {
        return self::mergeMessage([], $message);
    }

    /**
     * @param  array<string, mixed>  $current
     */
    public static function mergeMessage(array $current, DiscordMessageDTO $message): self
    {
        $nestedUser = $current['user'] ?? $current['author'] ?? [];

        if (!is_array($nestedUser)) {
            $nestedUser = [];
        }

        $defaults = [
            'author' => $message->authorRaw,
            'username' => $nestedUser['username'] ?? $message->authorUsername,
            'global_name' => $nestedUser['global_name'] ?? $message->authorName,
            'avatar' => array_key_exists('avatar', $nestedUser)
                ? $nestedUser['avatar']
                : ($message->authorRaw['avatar'] ?? null),
        ];

        return new self(array_replace($defaults, $current));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->payload;
    }
}
