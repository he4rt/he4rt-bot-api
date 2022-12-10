<?php

namespace App\Repositories\Feedback;

use App\Models\Feedback\FeedbackReview;
use App\Repositories\BaseRepository;

class FeedbackReviewRepository extends BaseRepository
{
    public function __construct(FeedbackReview $model)
    {
        parent::__construct($model);
    }
}
