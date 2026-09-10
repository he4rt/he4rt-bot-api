<?php

declare(strict_types=1);

namespace He4rt\Identity\User\ValueObjects;

use He4rt\Identity\User\Exceptions\UsernameException;
use He4rt\Identity\User\Models\User;

final class UsernameValidator
{
    /**
     * @var list<string>
     */
    public const array RESERVED_USERNAMES = [
        'admin',
        'administrator',
        'system',
        'root',
        'he4rt',
        'heart',
        'he4rtdevs',
        'mod',
        'moderator',
        'staff',
        'support',
        'help',
        'api',
        'bot',
        'null',
        'undefined',
        'anonymous',
        'everyone',
        'here',
    ];

    /**
     * @throws UsernameException
     */
    public static function normalizeAndValidate(string $username, ?User $user = null): string
    {
        $normalized = mb_strtolower(mb_trim($username));
        $length = mb_strlen($normalized);

        if ($length < 2 || $length > 32) {
            throw UsernameException::invalidFormat(
                'o tamanho deve ter entre 2 e 32 caracteres',
                'panel-app::profile.validation.username_reason_length',
            );
        }

        if (!preg_match('/^[a-z0-9._-]+$/', $normalized)) {
            throw UsernameException::invalidFormat(
                'apenas letras, números, sublinhado (_), hífen (-) e ponto (.) são permitidos',
                'panel-app::profile.validation.username_reason_characters',
            );
        }

        if (!preg_match('/^[a-z0-9]/', $normalized) || !preg_match('/[a-z0-9]$/', $normalized)) {
            throw UsernameException::invalidFormat(
                'não pode começar ou terminar com caracteres especiais',
                'panel-app::profile.validation.username_reason_edges',
            );
        }

        if (preg_match('/[._-]{2,}/', $normalized)) {
            throw UsernameException::invalidFormat(
                'não pode conter caracteres especiais consecutivos',
                'panel-app::profile.validation.username_reason_consecutive',
            );
        }

        if (in_array($normalized, self::RESERVED_USERNAMES, strict: true)) {
            throw UsernameException::reservedUsername($normalized);
        }

        $adminsConfig = (string) config('he4rt.admins', '');
        $adminEntries = array_filter(array_map(trim(...), explode(',', $adminsConfig)));
        $adminNames = array_map(strtolower(...), $adminEntries);

        if (in_array($normalized, $adminNames, strict: true)) {
            $isOwnAdminUsername = $user instanceof User && (
                mb_strtolower($user->username) === $normalized
                || in_array(mb_strtolower($user->id), $adminNames, strict: true)
            );

            if (!$isOwnAdminUsername) {
                throw UsernameException::reservedUsername($normalized);
            }
        }

        return $normalized;
    }

    /**
     * @throws UsernameException
     */
    public static function validate(string $username, ?User $user = null): string
    {
        return self::normalizeAndValidate($username, $user);
    }
}
