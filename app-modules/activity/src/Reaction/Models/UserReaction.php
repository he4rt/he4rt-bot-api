<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Database\Factories\ReactionFactory;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $timeline_id
 * @property TimelineReaction $reaction
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $user
 * @property-read Timeline $timeline
 */
#[UseFactory(factoryClass: ReactionFactory::class)]
#[Table(name: 'activity_user_reactions')]
final class UserReaction extends Model
{
    /** @use HasFactory<ReactionFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Timeline, $this> */
    public function timeline(): BelongsTo
    {
        return $this->belongsTo(Timeline::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reaction' => TimelineReaction::class,
        ];
    }
}
