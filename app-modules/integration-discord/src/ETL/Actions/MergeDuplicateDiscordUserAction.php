<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;

final class MergeDuplicateDiscordUserAction
{
    /**
     * Move all identities and FKs from $newUser to $oldUser, then delete $newUser.
     * Updates $oldUser->username to $targetUsername (current Discord handle).
     *
     * @return array{moved_identities: int, moved_fks: array<string, int>}
     */
    public function handle(User $oldUser, User $newUser, string $targetUsername): array
    {
        return DB::transaction(function () use ($oldUser, $newUser, $targetUsername): array {
            $movedIdentities = ExternalIdentity::query()
                ->where('model_id', $newUser->id)
                ->update(['model_id' => $oldUser->id]);

            ExternalIdentity::query()
                ->where('connected_by', $newUser->id)
                ->update(['connected_by' => $oldUser->id]);

            $fkMoves = $this->reassignFkRelations($oldUser, $newUser);

            $this->mergeOneToOneRelations($oldUser, $newUser);

            $newUser->delete();

            if ($oldUser->username_manually_set_at === null && $oldUser->username !== $targetUsername) {
                $taken = User::query()
                    ->where('username', $targetUsername)
                    ->where('id', '!=', $oldUser->id)
                    ->exists();

                if (!$taken) {
                    $oldUser->update(['username' => $targetUsername]);
                }
            }

            return [
                'moved_identities' => $movedIdentities,
                'moved_fks' => $fkMoves,
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    private function reassignFkRelations(User $old, User $new): array
    {
        $simpleUserIdTables = [
            'characters',
            'meeting_participants',
        ];

        $stats = [];

        foreach ($simpleUserIdTables as $table) {
            $stats[$table] = DB::table($table)
                ->where('user_id', $new->id)
                ->update(['user_id' => $old->id]);
        }

        $stats['meetings'] = DB::table('meetings')
            ->where('admin_id', $new->id)
            ->update(['admin_id' => $old->id]);

        $stats['feedbacks_target'] = DB::table('feedbacks')
            ->where('target_id', $new->id)
            ->update(['target_id' => $old->id]);

        $stats['feedbacks_sender'] = DB::table('feedbacks')
            ->where('sender_id', $new->id)
            ->update(['sender_id' => $old->id]);

        $stats['feedback_reviews'] = DB::table('feedback_reviews')
            ->where('staff_id', $new->id)
            ->update(['staff_id' => $old->id]);

        return $stats;
    }

    private function mergeOneToOneRelations(User $old, User $new): void
    {
        foreach (['user_address', 'user_information'] as $table) {
            $oldHas = DB::table($table)->where('user_id', $old->id)->exists();
            $newHas = DB::table($table)->where('user_id', $new->id)->exists();

            if ($oldHas && $newHas) {
                DB::table($table)->where('user_id', $new->id)->delete();
            } elseif ($newHas) {
                DB::table($table)->where('user_id', $new->id)->update(['user_id' => $old->id]);
            }
        }
    }
}
