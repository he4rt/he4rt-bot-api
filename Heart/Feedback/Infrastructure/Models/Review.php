<?php

namespace Heart\Feedback\Infrastructure\Models;

use Heart\Feedback\Domain\Enums\ReviewTypeEnum;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasUuids;

    protected $table = 'feedback_reviews';

    protected $fillable = [
        'id',
        'feedback_id',
        'staff_id',
        'status',
        'reason',
        'received_at',
    ];

    protected $casts = [
        'status' => ReviewTypeEnum::class,
        'received_at' => 'timestamp',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
