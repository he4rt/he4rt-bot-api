<?php

namespace Heart\Feedback;

use Heart\Core\Contracts\DomainInterface;
use Heart\Feedback\Infrastructure\Providers\FeedbackRouteProvider;
use Heart\Feedback\Infrastructure\Providers\FeedbackServiceProvider;

class FeedbackDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            FeedbackServiceProvider::class,
            FeedbackRouteProvider::class,
        ];
    }
}
