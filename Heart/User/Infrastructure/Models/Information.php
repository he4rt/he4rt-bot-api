<?php

namespace Heart\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Information extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'name',
        'nickname',
        'linkedin_url',
        'github_url',
        'birthdate',
        'about'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
