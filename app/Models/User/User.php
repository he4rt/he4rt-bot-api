<?php

namespace App\Models\User;

use App\Models\Events\Badge;
use App\Models\Gamefication\ExperienceTable;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Lumen\Auth\Authorizable;
use Laravel\Passport\HasApiTokens;

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
        'is_donator'
    ];

    protected $casts = [
        'discord_id' => 'int',
        'is_donator' => 'boolean'
    ];


    protected $dates = ['daily'];

    public function validateForPassportPasswordGrant($password)
    {
        return (int)$password == (int)$this->discord_id;
    }

    public function nextLevel(): HasOne
    {
        return $this->hasOne(ExperienceTable::class, 'id', 'level');
    }

    public function levelUp(int $currentExp): void
    {
        $currentLevel = $this->attributes['level'] + 1;

        $this->update(['level' => $currentLevel, 'current_exp' => $currentExp]);
        $this->levelupLog()->create(['season_id' => config('he4rt.season'), 'level' => $currentLevel]);
    }

    public function wipe()
    {
        $this->update([
            'level' => 1,
            'current_exp' => 0
        ]);
    }

    public function dailyPoints(int $value)
    {
        $this->update([
            'money' => $this->attributes['money'] + $value,
            'daily' => Carbon::now()->addDay()
        ]);

        return $this;
    }

    public function updateMoney(string $operation, $value)
    {
        $balance = $operation == "add" ?
            $this->attributes['money'] + $value :
            $this->attributes['money'] - $value;

        $this->update([
            'money' => $balance
        ]);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function seasonMessagesCount(): int
    {
        return $this->messages()->where('season_id', config('he4rt.season'))->count();
    }

    public function seasonInfo()
    {
        return $this->hasMany(Season::class);
    }

    public function levelupLog()
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

    public function hasBadge(int $badgeId): bool
    {
        return (bool) $this->badges()->find($badgeId);
    }
}
