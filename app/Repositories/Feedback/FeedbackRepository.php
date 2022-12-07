<?php

namespace App\Repositories\Feedback;

use App\Models\Feedback\Feedback;
use App\Repositories\BaseRepository;

class FeedbackRepository extends BaseRepository
{
    public function __construct(Feedback $model)
    {
        parent::__construct($model);
    }
}
