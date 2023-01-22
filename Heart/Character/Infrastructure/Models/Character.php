<?php

namespace Heart\Character\Infrastructure\Models;

use Heart\Character\Infrastructure\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $user_id
 * @property int reputation
 * @property int $experience
 * @property \Carbon\Carbon $daily_bonus_claimed_at
 */
class Character extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'characters';

    protected $fillable = [
        'user_id',
        'reputation',
        'experience',
        'daily_bonus_claimed_at'
    ];

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    protected static function newFactory(): CharacterFactory
    {
        return CharacterFactory::new();
    }
}
