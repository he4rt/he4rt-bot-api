<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Listeners;

use He4rt\Activity\Tracking\Actions\TrackActivity;
use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\AttributionMethod;
use He4rt\Contents\Articles\Events\ArticlePublished;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

final readonly class TrackContentContribution
{
    public function __construct(private TrackActivity $trackActivity) {}

    public function handle(ArticlePublished $event): void
    {
        $entry = $event->entry;

        $identityProvider = $entry->provider->toIdentityProvider();

        if ($identityProvider === null) {
            return;
        }

        /** @var ExternalIdentity|null $identity */
        $identity = $entry->author?->providers()
            ->where('provider', $identityProvider)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        // Sem identidade conectada não há dono possível — mesma regra de toda fonte.
        if ($identity === null) {
            return;
        }

        $this->trackActivity->handle(new TrackActivityDTO(
            externalIdentityId: $identity->id,
            type: ActivityType::Article,
            attributedBy: AttributionMethod::Owned,
            occurredAt: $entry->published_at->toDateTimeImmutable(),
            externalRef: sprintf('%s:article:%s', $entry->provider->value, $entry->external_id),
            sourceType: 'content_entry',
            sourceId: $entry->id,
        ));
    }
}
