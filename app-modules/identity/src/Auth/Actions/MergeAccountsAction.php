<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\Events\AccountsMerged;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class MergeAccountsAction
{
    public function execute(User $currentUser, User $oldUser): void
    {
        DB::transaction(function () use ($currentUser, $oldUser): void {
            ExternalIdentity::query()
                ->where('model_type', (new User)->getMorphClass())
                ->where('model_id', $currentUser->id)
                ->update(['model_id' => $oldUser->id]);

            ExternalIdentity::query()
                ->withTrashed()
                ->where('connected_by', $currentUser->id)
                ->update(['connected_by' => $oldUser->id]);

            event(new AccountsMerged($oldUser->id, $currentUser->id));

            $currentUser->delete();

            $this->enrichOldUser($currentUser, $oldUser);
        });
    }

    private function enrichOldUser(User $source, User $target): void
    {
        $isFirstLogin = $target->first_login_at === null;

        if (!$isFirstLogin) {
            return;
        }

        $updates = ['first_login_at' => now()];

        if ($source->email !== null && $target->email === null) {
            $updates['email'] = $source->email;
        }

        if ($source->name !== $source->username && $target->name === $target->username) {
            $updates['name'] = $source->name;
        }

        $canUpdateUsername = $source->username !== $target->username
            && !User::query()
                ->where('username', $source->username)
                ->where('id', '!=', $target->id)
                ->exists();

        if ($canUpdateUsername) {
            $updates['username'] = $source->username;
        }

        try {
            DB::transaction(fn () => $target->update($updates));
        } catch (UniqueConstraintViolationException) {
            unset($updates['username']);
            $target->update($updates);
        }
    }
}
