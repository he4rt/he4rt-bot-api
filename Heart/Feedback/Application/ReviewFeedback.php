<?php

namespace Heart\Feedback\Application;

use Heart\Feedback\Domain\Actions\PersistFeedbackReview;
use Heart\Feedback\Domain\DTOs\FeedbackReviewDTO;
use Heart\Provider\Application\FindProvider;
use Heart\Provider\Domain\Enums\ProviderEnum;

class ReviewFeedback
{
    public function __construct(
        private readonly PersistFeedbackReview $persistReview,
        private readonly FindProvider $findProvider,
    ) {
    }

    public function handle(
        string $feedbackId,
        string $reviewType,
        string $providerAdminId,
        ?string $reason = null
    ): void {
        $providerEntity = $this->findProvider->handle(ProviderEnum::Discord->value, $providerAdminId);
        $reviewDTO = FeedbackReviewDTO::make($feedbackId, $reviewType, $providerEntity, $reason);

        $this->persistReview->handle($reviewDTO);
    }
}
