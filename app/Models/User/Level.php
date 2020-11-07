<?php


namespace App\Models\User;


use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $table = "user_levelup";

    protected $fillable = [
        'season_id', 'user_id', 'level'
    ];

}
