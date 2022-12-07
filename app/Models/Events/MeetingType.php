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

    public function getStartAtAttribute($value): string
    {
        $hours = (string) intdiv($value, 60);
        $minutes = (string) $value % 60;

        $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);

        return $hours . ':' . $minutes;
    }
}
