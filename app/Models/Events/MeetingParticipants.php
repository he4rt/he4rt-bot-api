<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingParticipants extends Model
{
    use HasFactory;

    protected $table = 'meeting_participants';

    protected $fillable = [
        'meeting_id',
        'user_id',
        'attend_at',
    ];

    protected $dates = [
        'attend_at'
    ];

    protected $casts = [
        'meeting_id' => 'integer',
        'user_id' => 'integer',
    ];
}
