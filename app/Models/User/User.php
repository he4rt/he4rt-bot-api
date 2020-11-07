<?php

namespace App\Models\User;

use App\Models\Gamification\Message;
use App\Repositories\Gamification\LevelupRepository;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Laravel\Passport\HasApiTokens;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory, HasApiTokens;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'discord_id',
        'twitch_id',
        'email',
        'level',
        'current_exp',
        'money',
        'git',
        'name',
        'nickname',
        'about',
        'daily',
        'reputation',
        'twitch'
    ];


    protected $dates = ['daily'];

    protected $appends = [
        'levelup_exp'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

    public function validateForPassportPasswordGrant($password)
    {
        return (int)$password == (int)$this->discord_id;
    }

    public function getlevelUpExpAttribute()
    {
        return app(LevelupRepository::class)->fetchExpTableLevel($this->attributes['level']);
    }

    public function updateExp(int $exp)
    {
        $this->update([
            'current_exp' => $this->attributes['current_exp'] + $exp
        ]);
    }

    public function levelUp()
    {
        $level = $this->attributes['level'] + 1;
        $this->update([
            'level' => $this->attributes['level'] + 1,
            'current_exp' => 0
        ]);

        return $level;
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

    public function seasonInfo()
    {
        return $this->hasMany(Season::class);
    }

    public function levelupLog()
    {
        return $this->hasMany(Level::class);
    }

}
