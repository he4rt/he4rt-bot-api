<?php


namespace App\Models\User;



use Illuminate\Database\Eloquent\Model;
use App\Models\Gamification\Season as SeasonModel;

class Season extends Model
{
    protected $table = "user_seasons";

    protected $fillable = [
        'user_id',
        'season_id',
        'level',
        'messages_count'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function season() {
        return $this->belongsTo(SeasonModel::class);
    }
}
