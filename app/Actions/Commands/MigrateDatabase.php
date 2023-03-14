<?php

namespace App\Actions\Commands;

use App\Models\Feedback\Feedback;
use App\Models\User\Message;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class MigrateDatabase
{
    private ConnectionInterface $v2;
    private array $users = [];

    public function __construct()
    {
        $this->v2 = DB::connection('shippuden');
    }

    public function run(): void
    {
        $this->createBetaTesterBadge();
        User::query()->chunk(500, fn($users) => $this->migrateChunk($users));

        Feedback::query()->each(fn(Feedback $feedback) => $this->migrateFeedback($feedback));
    }

    /**
     * @param Collection<int, User> $users
     * @return void
     */
    private function migrateChunk(Collection $users)
    {
        $users->map(fn(User $user) => $this->persistNewUser($user))
            ->map(fn(User $user) => $this->persistUserProvider($user))
            ->map(fn(User $user) => $this->persistCharacter($user))
            ->each(fn(User $user) => $this->persistBetaTesterBadge($user))
            ->each(fn(User $user) => $this->persistBasicInformation($user))
            ->each(fn(User $user) => $this->persistProviderMessages($user))
            ->each(fn(User $user) => $this->persistSeasonRanking($user))
            ->each(fn(User $user) => $this->persistLevelingLogs($user));
    }

    private function persistNewUser(User $user): User
    {
        $userId = Uuid::uuid4()->toString();
        $this->users[$user->id] = $userId;
        $this->v2->table('users')
            ->insert([
                'id' => $userId,
                'username' => sprintf('he4rt-%s-%s', Str::random(), rand(1000, 9999)),
                'is_donator' => $user->is_donator,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        $user->newId = $userId;
        return $user;
    }

    private function persistUserProvider(User $user): User
    {
        $providerId = Uuid::uuid4()->toString();
        $this->v2
            ->table('providers')
            ->insert([
                'id' => $providerId,
                'user_id' => $user->newId,
                'provider' => 'discord',
                'provider_id' => $user->discord_id,
                'email' => null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);

        $user->providerId = $providerId;

        return $user;
    }

    private function persistCharacter(User $user): User
    {
        $user->characterId = Uuid::uuid4()->toString();
        $this->v2
            ->table('characters')
            ->insert([
                'id' => $user->characterId,
                'user_id' => $user->newId,
                'experience' => 1,
                'reputation' => $user->reputation ?? 0,
                'daily_bonus_claimed_at' => $user->daily,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        return $user;
    }

    private function persistBasicInformation(User $user): void
    {
        $this->v2
            ->table('user_address')
            ->insert([
                'id' => Uuid::uuid4()->toString(),
                'user_id' => $user->newId,
                'state' => $user->uf ?? null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);

        $this->v2
            ->table('user_information')
            ->insert([
                'id' => Uuid::uuid4()->toString(),
                'user_id' => $user->newId,
                'name' => $user->name ?? null,
                'nickname' => $user->nickname ?? null,
                'github_url' => $user->git ?? null,
                'linkedin_url' => $user->linkedin ?? null,
                'about' => $user->about ?? null,
                'birthdate' => null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
    }

    private function persistProviderMessages(User $user): void
    {
        $user->messages()
            ->chunk(500, function (Collection $messages) use ($user) {
                $messages->each(function (Message $message) use ($user) {
                    $this->v2
                        ->table('messages')
                        ->insert([
                            'id' => Uuid::uuid4()->toString(),
                            'provider_id' => $user->providerId,
                            'season_id' => $message->season_id,
                            'channel_id' => $message->channel_id ?? null,
                            'content' => $message->message_content,
                            'obtained_experience' => $message->obtained_experience,
                            'created_at' => $message->created_at,
                            'updated_at' => $message->updated_at,
                            'sent_at' => $message->sent_at ?? null,
                        ]);
                });
            });
    }

    private function persistSeasonRanking(User $user): void
    {
        foreach ($user->seasonInfo as $pastSeason) {
            $this->v2
                ->table('seasons_rankings')
                ->insert([
                    'id' => Uuid::uuid4()->toString(),
                    'season_id' => $pastSeason->season_id,
                    'character_id' => $user->characterId,
                    'ranking_position' => $pastSeason->ranking_position,
                    'level' => $pastSeason->level,
                    'experience' => $pastSeason->experience,
                    'messages_count' => $pastSeason->meetings_count,
                    'badges_count' => $pastSeason->badges_count,
                    'meetings_count' => $pastSeason->meetings_count,
                ]);
        }
    }

    private function persistLevelingLogs(User $user)
    {
        foreach ($user->levelupLog()->get() as $levelingLog) {
            $this->v2
                ->table('characters_leveling_logs')
                ->insert([
                    'id' => Uuid::uuid4()->toString(),
                    'season_id' => $levelingLog->season_id,
                    'character_id' => $user->characterId,
                    'level' => $levelingLog->level,
                    'created_at' => $levelingLog->created_at,
                    'updated_at' => $levelingLog->updated_at
                ]);
        }
    }

    private function migrateFeedback(Feedback $feedback)
    {
        $feedbackId = Uuid::uuid4()->toString();
        $this->v2
            ->table('feedbacks')
            ->insert([
                'id' => $feedbackId,
                'sender_id' => $this->users[$feedback->sender_id],
                'target_id' => $this->users[$feedback->target_id],
                'type' => $feedback->type,
                'message' => $feedback->message,
                'created_at' => $feedback->created_at,
                'updated_at' => $feedback->updated_at,
            ]);

        $review = $feedback->reviews[0];
        $this->v2
            ->table('feedback_reviews')
            ->insert([
                'id' => Uuid::uuid4()->toString(),
                'feedback_id' => $feedbackId,
                'staff_id' => $this->users[$review->staff_id],
                'status' => is_null($review->approved_at) ? 'declined' : 'approved',
                'reason' => $review->decline_message ?? null,
                'received_at' => $review->approved_at ?: $review->declined_at,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ]);
    }

    private function persistBetaTesterBadge(User $user)
    {
        if ($user->level >= 2) {
            $this->v2
                ->table('characters_badges')
                ->insert([
                    'character_id' => $user->characterId,
                    'badge_id' => 1,
                    'claimed_at' => Carbon::now()
                ]);
        }
    }

    private function createBetaTesterBadge()
    {
        $badge = [
            'id' => 1,
            'provider' => 'discord',
            'name' => '2023 Beta Tester',
            'description' => 'Você participou dos meses de teste da nossa comunidade!',
            'image_url' => 'https://place-hold.it/300/300',
            'redeem_code' => 'pre-season-2023',
            'active' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        $this->v2
            ->table('badges')
            ->insert($badge);
    }
}
