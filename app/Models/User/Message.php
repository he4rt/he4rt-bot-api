<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property User $user
 * @property string $message
 * @property int $season_id
 * @property int $obtained_experience
 */
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
