<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Models;

use Carbon\CarbonInterface;
use He4rt\Economy\Concerns\HasWallet;
use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Database\Factories\CharacterFactory;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Date;

/**
 * @property string $id
 * @property int $user_id
 * @property int $reputation
 * @property int $experience
 * @property CarbonInterface|null $daily_bonus_claimed_at
 * @property int $level
 * @property float $percentage_experience
 * @property bool $can_claim_daily_bonus
 */
#[Appends([
    'ranking',
    'level',
])]
#[Table(name: 'characters')]
final class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;
    use HasUuids;
    use HasWallet;

    public const array LEVEL_THRESHOLDS = [
        1 => 0, 2 => 120, 3 => 250, 4 => 510, 5 => 1_000,
        6 => 1_500, 7 => 2_100, 8 => 2_800, 9 => 3_600, 10 => 4_500,
        11 => 5_500, 12 => 6_650, 13 => 7_800, 14 => 9_100, 15 => 10_500,
        16 => 12_000, 17 => 13_700, 18 => 15_500, 19 => 17_500, 20 => 20_000,
        21 => 23_000, 22 => 26_500, 23 => 30_000, 24 => 34_500, 25 => 39_000,
        26 => 44_000, 27 => 49_500, 28 => 55_500, 29 => 62_000, 30 => 69_000,
        31 => 77_000, 32 => 85_500, 33 => 95_000, 34 => 105_000, 35 => 116_000,
        36 => 128_000, 37 => 141_000, 38 => 155_000, 39 => 170_000, 40 => 190_000,
        41 => 210_000, 42 => 230_000, 43 => 250_000, 44 => 270_000, 45 => 290_000,
        46 => 310_000, 47 => 330_000, 48 => 350_000, 49 => 370_000, 50 => 400_000,
    ];

    public static function generateTextExperience(string $message, int $level, bool $isSupporter): int
    {
        $experience = max(1, (int) ((mb_strlen($message) * 0.01) + ($level * 0.1)));

        return $isSupporter ? $experience * 2 : $experience;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Badge, $this, Pivot>
     */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(
            Badge::class,
            'characters_badges',
            'character_id',
            'badge_id'
        )->withPivot(['claimed_at']);
    }

    /**
     * @return HasMany<PastSeason, $this>
     */
    public function pastSeasons(): HasMany
    {
        return $this->hasMany(PastSeason::class);
    }

    protected static function newFactory(): CharacterFactory
    {
        return CharacterFactory::new();
    }

    protected function getRankingAttribute(): int
    {
        $position = $this->newQuery()
            ->orderByDesc('experience')
            ->pluck('id')
            ->filter(fn ($id) => $id === $this->getKey())
            ->keys()
            ->first();

        return (int) $position + 1;
    }

    /**
     * @return Attribute<int, never>
     */
    protected function level(): Attribute
    {
        return Attribute::get(function (): int {
            $currentLevel = 1;

            foreach (self::LEVEL_THRESHOLDS as $level => $threshold) {
                if ($this->experience >= $threshold) {
                    $currentLevel = $level;
                }
            }

            return $currentLevel;
        });
    }

    /**
     * @return Attribute<int, never>
     */
    protected function experienceProgress(): Attribute
    {
        return Attribute::get(function (): int {
            $currentLevel = $this->level;
            $currentThreshold = self::LEVEL_THRESHOLDS[$currentLevel] ?? 0;
            $nextThreshold = self::LEVEL_THRESHOLDS[$currentLevel + 1] ?? $currentThreshold;

            return $nextThreshold - $this->experience;
        });
    }

    /**
     * @return Attribute<float, never>
     */
    protected function percentageExperience(): Attribute
    {
        return Attribute::get(function (): float {
            $currentLevel = $this->level;
            $currentThreshold = self::LEVEL_THRESHOLDS[$currentLevel] ?? 0;
            $nextThreshold = self::LEVEL_THRESHOLDS[$currentLevel + 1] ?? $currentThreshold;
            $range = $nextThreshold - $currentThreshold;

            if ($range <= 0) {
                return 100.0;
            }

            return round(($this->experience - $currentThreshold) / $range * 100, 2);
        });
    }

    /**
     * @return Attribute<float, never>
     */
    protected function experiencePercentageRemaining(): Attribute
    {
        return Attribute::get(fn (): float => round(100.0 - $this->percentage_experience, 2));
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function canClaimDailyBonus(): Attribute
    {
        return Attribute::get(function (): bool {
            if (!$this->daily_bonus_claimed_at) {
                return true;
            }

            return Date::parse($this->daily_bonus_claimed_at)->diffInHours(now()) >= 24;
        });
    }
}
