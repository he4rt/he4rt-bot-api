<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $table = 'meetings';

    protected $fillable = [
        'meeting_type_id',
        'user_created_id',
        'starts_at',
        'ends_at',
    ];

    protected $dates = [
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'meeting_type_id' => 'integer',
        'user_created_id' => 'integer',
    ];

    public function isEnded(): bool
    {
        return (bool) $this->attributes['ends_at'];
    }
}
