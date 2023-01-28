<?php

namespace Heart\Character\Infrastructure\Models;

use Heart\Character\Infrastructure\Factories\CharacterFactory;
use Heart\Badges\Infrastructure\Model\Badge;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $user_id
 * @property int reputation
 * @property int $experience
 * @property \Carbon\Carbon $daily_bonus_claimed_at
 */
class Character extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'characters';

    protected $fillable = [
        'id',
        'user_id',
        'reputation',
        'experience',
        'daily_bonus_claimed_at'
    ];

    protected $appends = [
        'ranking'
    ];

    public function getRankingAttribute(): int
    {
        return $this->newQuery()
                ->orderByDesc('experience')
                ->pluck('id')
                ->filter(fn($id) => $id == $this->getKey())
                ->keys()
                ->first() + 1;
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(
            Badge::class,
            'characters_badges',
            'character_id',
            'badge_id'
        )->withPivot(['claimed_at']);
    }

    public function pastSeasons(): HasMany
    {
        return $this->hasMany(PastSeason::class);
    }

    protected static function newFactory(): CharacterFactory
    {
        return CharacterFactory::new();
    }
}
