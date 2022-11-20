<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = "user_messages";

    protected $fillable = [
        'user_id',
        'season_id',
        'message',
        'obtained_experience'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
