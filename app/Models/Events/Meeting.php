<?php

namespace App\Models\Events;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meeting extends Model
{
    use HasFactory;

    protected $table = 'meetings';

    protected $fillable = [
        'content',
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
        return (bool)$this->attributes['ends_at'];
    }

    public function meetingType(): HasOne
    {
        return $this->hasOne(MeetingType::class, 'id', 'meeting_type_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'meeting_participants',
            'meeting_id',
            'user_id'
        )->withPivot(['attend_at']);
    }
}
