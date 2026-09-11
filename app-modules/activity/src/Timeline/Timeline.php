<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline;

use Carbon\CarbonInterface;
use He4rt\Activity\Database\Factories\TimelineFactory;
use He4rt\Activity\Reaction\Concerns\HasReactions;
use He4rt\Activity\Reaction\Models\UserReaction;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $postable_type
 * @property string $postable_id
 * @property string|null $root_id
 * @property string|null $parent_id
 * @property bool $is_ignored
 * @property bool $pinned
 * @property int $views
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Table(name: 'activity_timeline')]
final class Timeline extends Model
{
    /** @use HasFactory<TimelineFactory> */
    use HasFactory;
    use HasReactions;
    use HasUuids;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function postable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<self, $this> */
    public function root(): BelongsTo
    {
        return $this->belongsTo(self::class, 'root_id');
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<UserReaction, $this> */
    public function userReactions(): HasMany
    {
        return $this->hasMany(UserReaction::class);
    }

    protected static function newFactory(): TimelineFactory
    {
        return TimelineFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'user_id' => 'string',
            'root_id' => 'string',
            'parent_id' => 'string',
            'is_ignored' => 'boolean',
            'pinned' => 'boolean',
        ];
    }
}
