<?php

namespace Database\Factories\Feedback;

use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackReview;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackReviewFactory extends Factory
{
    protected $model = FeedbackReview::class;
    public function definition(): array
    {
        return [
            'feedback_id' => Feedback::factory(),
            'staff_id' => User::factory(),
            'decline_message' => 'ah mano para',
            'approved_at' => Carbon::now(),
            'declined_at' => null
        ];
    }
}
