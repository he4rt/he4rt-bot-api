<?php

namespace App\Models\User;

use App\Models\Gamefication\Season as SeasonModel;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $table = "user_seasons";

    protected $fillable = [
        'user_id',
        'season_id',
        'level',
        'messages_count',
        'badges_count',
        'messages_count'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function season()
    {
        return $this->belongsTo(SeasonModel::class);
    }
}
