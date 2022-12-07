<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingType extends Model
{
    use HasFactory;

    protected $table = 'meeting_types';

    protected $fillable = [
        'name',
        'week_day',
        'start_at',
    ];
}
