<?php

namespace Heart\Message\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{

    protected $fillable = [
        'id',
        'provider_id',
        'provider_message_id',
        'season_id',
        'channel_id',
        'content',
        'sent_at',
        'obtained_experience',
    ];
}
