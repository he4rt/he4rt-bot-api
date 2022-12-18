<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    protected $table = "user_messages";

    protected $fillable = [
        'user_id',
        'season_id',
        'message',
        'obtained_experience'
    ];

    protected $casts = [
        'user_id' => 'int',
        'season_id' => 'int',
        'message' => 'string',
        'obtained_experience' => 'int'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
