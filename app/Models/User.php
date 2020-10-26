<?php

namespace App\Models;

use App\Models\Gamification\Message;
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

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

    public function validateForPassportPasswordGrant($password)
    {
        return (int)$password == (int)$this->discord_id;
    }

    public function updateExp(int $exp)
    {
        $this->update([
            'current_exp' => $this->attributes['current_exp'] + $exp
        ]);
    }

    public function levelUp()
    {
        $this->update([
            'level' => $this->attributes['level'] + 1,
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


    public function messages()
    {
        return $this->hasMany(Message::class);
    }

}
