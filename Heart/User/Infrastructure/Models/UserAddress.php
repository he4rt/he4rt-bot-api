<?php

namespace Heart\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'country',
        'state',
        'city',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
