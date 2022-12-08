<?php

namespace App\Models\Feedback;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackReview extends Model
{
    use HasFactory;

    protected $table = 'feedback_reviews';

    protected $fillable = [
        'feedback_id',
        'staff_id',
        'decline_message',
        'approved_at',
        'declined_at',
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
