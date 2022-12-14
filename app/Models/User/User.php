<?php

declare(strict_types=1);

namespace App\Models\User;

use App\Models\Events\Badge;
use App\Models\Events\Meeting;
use App\Models\Feedback\Feedback;
use App\Models\Gamefication\ExperienceTable;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Lumen\Auth\Authorizable;
use Laravel\Passport\HasApiTokens;

/**
 * @property string $discord_id
 * @property string $twitch_id
 * @property string $email
 * @property int $money
 * @property bool $is_donator
 * @property ExperienceTable $nextLevel
 * @property int $current_exp
 * @property int $level
 * @property string $uf
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable;
    use Authorizable;
    use HasFactory;
    use HasApiTokens;

    protected $fillable = [
        'discord_id',
        'twitch_id',
        'email',
        'level',
        'current_exp',
        'money',
        'git',
        'linkedin',
        'name',
        'nickname',
        'about',
        'daily',
        'reputation',
        'is_donator',
        'uf',
    ];

    protected $casts = [
        'discord_id' => 'int',
        'is_donator' => 'boolean'
    ];

    protected $dates = ['daily'];

    public function nextLevel(): HasOne
    {
        return $this->hasOne(ExperienceTable::class, 'id', 'level');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function seasonMessagesCount(): int
    {
        return $this->messages()->where('season_id', config('he4rt.season'))->count();
    }

    public function seasonInfo(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    public function levelupLog(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(
            Badge::class,
            'user_badges',
            'user_id',
            'badge_id'
        );
    }

    public function sentFeedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'sender_id');
    }

    public function receivedFeedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'target_id');
    }

    public function validateForPassportPasswordGrant($password): bool
    {
        return (int)$password == (int)$this->discord_id;
    }

    public function levelUp(int $currentExp): void
    {
        $currentLevel = $this->attributes['level'] + 1;

        $this->update(['level' => $currentLevel, 'current_exp' => $currentExp]);
        $this->levelupLog()->create(['season_id' => config('he4rt.season'), 'level' => $currentLevel]);
    }

    public function wipe(): void
    {
        $this->update([
            'level' => 1,
            'current_exp' => 0
        ]);
    }

    public function dailyPoints(int $value): self
    {
        $this->update([
            'money' => $this->attributes['money'] + $value,
            'daily' => Carbon::now()->addDay()
        ]);

        return $this;
    }

    public function updateMoney(string $operation, $value): void
    {
        $balance = $operation == "add" ?
            $this->attributes['money'] + $value :
            $this->attributes['money'] - $value;

        $this->update([
            'money' => $balance
        ]);
    }

    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(
            Meeting::class,
            'meeting_participants',
            'user_id',
            'meeting_id'
        )->withPivot(['attend_at']);
    }

    public function hasBadge(int $badgeId): bool
    {
        return (bool) $this->badges()->find($badgeId);
    }
}
