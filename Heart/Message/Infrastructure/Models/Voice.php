<?php

namespace Heart\Message\Infrastructure\Models;

use Heart\Provider\Infrastructure\Models\Provider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voice extends Model
{
    protected $table = 'voice_messages';

    protected $fillable = [
        'provider_id',
        'season_id',
        'channel_name',
        'state',
        'obtained_experience'
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
