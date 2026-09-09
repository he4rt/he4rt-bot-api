<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class EnrichUserOnFirstLogin
{
    public function execute(User $user, OAuthUserDTO $oauthUser): User
    {
        if ($user->first_login_at !== null) {
            return $user;
        }

        $updates = ['first_login_at' => now()];

        if ($oauthUser->email !== null) {
            $updates['email'] = $oauthUser->email;
        }

        if ($oauthUser->name !== '') {
            $updates['name'] = $oauthUser->name;
        }

        $canUpdateUsername = $user->username_manually_set_at === null
            && $oauthUser->username !== $user->username
            && !User::query()
                ->where('username', $oauthUser->username)
                ->where('id', '!=', $user->id)
                ->exists();

        if ($canUpdateUsername) {
            $updates['username'] = $oauthUser->username;
        }

        try {
            DB::transaction(fn () => $user->update($updates));
        } catch (UniqueConstraintViolationException) {
            unset($updates['username']);
            $user->update($updates);
        }

        return $user->refresh();
    }
}
