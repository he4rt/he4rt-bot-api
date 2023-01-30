<?php

namespace Heart\Feedback\Infrastructure\Models;

use Heart\Feedback\Infrastructure\Factories\FeedbackFactory;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Feedback extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'feedbacks';

    protected $fillable = [
        'id',
        'sender_id',
        'target_id',
        'type',
        'message'
    ];

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    protected static function newFactory(): FeedbackFactory
    {
        return FeedbackFactory::new();
    }
}
