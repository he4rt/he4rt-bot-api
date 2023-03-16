<?php

namespace Heart\Feedback\Domain\DTOs;

use Heart\Feedback\Domain\Enums\ReviewTypeEnum;
use Heart\Provider\Domain\Entities\ProviderEntity;
use JsonSerializable;

class FeedbackReviewDTO implements JsonSerializable
{
    public function __construct(
        public readonly string $feedbackId,
        public readonly ReviewTypeEnum $reviewTypeEnum,
        public readonly ProviderEntity $adminProviderEntity,
        public readonly ?string $reason,
    ) {
    }

    public static function make(
        string $feedbackId,
        string $reviewType,
        ProviderEntity $providerEntity,
        ?string $reason
    ): self {
        return new self(
            feedbackId: $feedbackId,
            reviewTypeEnum: ReviewTypeEnum::from($reviewType),
            adminProviderEntity: $providerEntity,
            reason: $reason
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'feedback_id' => $this->feedbackId,
            'staff_id' => $this->adminProviderEntity->userId,
            'status' => $this->reviewTypeEnum->value,
            'reason' => $this->reason,
            'received_at' => $this->reason,
        ];
    }
}
