<?php


namespace App\Models\Gamification;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = "user_messages";

    protected $fillable = [
        'user_id', 'message'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
