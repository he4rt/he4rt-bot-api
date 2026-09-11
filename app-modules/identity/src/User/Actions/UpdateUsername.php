<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Actions;

use He4rt\Identity\User\Exceptions\UsernameException;
use He4rt\Identity\User\Models\User;
use He4rt\Identity\User\ValueObjects\UsernameValidator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class UpdateUsername
{
    /**
     * @throws UsernameException
     */
    public function handle(User $user, string $newUsername): User
    {
        $normalized = UsernameValidator::normalizeAndValidate($newUsername, $user);

        if ($normalized === mb_strtolower($user->username)) {
            throw UsernameException::sameAsCurrent();
        }

        if ($user->username_updated_at !== null && !$user->isAdmin()) {
            $cooldownDays = (int) config('he4rt.username_cooldown_days', 7);
            $availableAt = $user->username_updated_at->copy()->addDays($cooldownDays);

            if (now()->lessThan($availableAt)) {
                throw UsernameException::cooldownActive($availableAt);
            }
        }

        $isTaken = User::query()
            ->whereRaw('LOWER(username) = ?', [$normalized])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($isTaken) {
            throw UsernameException::alreadyTaken($normalized);
        }

        try {
            DB::transaction(function () use ($user, $normalized): void {
                $user->update([
                    'username' => $normalized,
                    'username_updated_at' => now(),
                    'username_manually_set_at' => $user->username_manually_set_at ?? now(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw UsernameException::alreadyTaken($normalized);
        }

        return $user->refresh();
    }

    /**
     * Alias for handle
     *
     * @throws UsernameException
     */
    public function execute(User $user, string $newUsername): User
    {
        return $this->handle($user, $newUsername);
    }
}
