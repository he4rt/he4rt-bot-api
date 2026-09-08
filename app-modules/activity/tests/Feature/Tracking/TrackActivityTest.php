<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Tracking\Actions\TrackActivity;
use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\AttributionMethod;
use He4rt\Activity\Tracking\Events\InteractionTracked;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Economy\Models\Transaction;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event;

function connectedIdentity(?User $user = null, IdentityProvider $provider = IdentityProvider::GitHub): ExternalIdentity
{
    $user ??= User::factory()->create();

    return ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => $provider,
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);
}

test('registro cria a interação e deriva o dono da identidade', function (): void {
    Event::fake([InteractionTracked::class]);

    $user = User::factory()->create();
    $identity = connectedIdentity($user);

    $interaction = resolve(TrackActivity::class)->handle(new TrackActivityDTO(
        externalIdentityId: $identity->id,
        type: ActivityType::PrMerged,
        attributedBy: AttributionMethod::ExternalId,
        occurredAt: CarbonImmutable::parse('2026-08-01 10:00:00'),
        externalRef: 'github:pr_merged:he4rt/api:474',
    ));

    expect($interaction->external_identity_id)->toBe($identity->id)
        ->and($interaction->user_id)->toBe($user->id)
        ->and($interaction->type)->toBe(ActivityType::PrMerged)
        ->and($interaction->isVisible())->toBeTrue();

    Event::assertDispatched(InteractionTracked::class);
});

test('registro não credita carteira nem incrementa xp', function (): void {
    $identity = connectedIdentity();

    resolve(TrackActivity::class)->handle(new TrackActivityDTO(
        externalIdentityId: $identity->id,
        type: ActivityType::Commit,
        attributedBy: AttributionMethod::ExternalId,
        occurredAt: CarbonImmutable::now(),
        externalRef: 'github:commit:he4rt/api:abc123',
    ));

    expect(Transaction::query()->count())->toBe(0)
        ->and(Character::query()->count())->toBe(0);
});

test('registro repetido do mesmo external_ref é no-op', function (): void {
    Event::fake([InteractionTracked::class]);

    $identity = connectedIdentity();

    $dto = new TrackActivityDTO(
        externalIdentityId: $identity->id,
        type: ActivityType::Review,
        attributedBy: AttributionMethod::ExternalId,
        occurredAt: CarbonImmutable::now(),
        externalRef: 'github:review:he4rt/api:8471023',
    );

    $first = resolve(TrackActivity::class)->handle($dto);
    $second = resolve(TrackActivity::class)->handle($dto);

    expect($first->id)->toBe($second->id)
        ->and(Interaction::query()->count())->toBe(1);

    Event::assertDispatchedTimes(InteractionTracked::class, 1);
});

test('abertura e merge do mesmo PR coexistem como fatos distintos', function (): void {
    $identity = connectedIdentity();

    foreach ([ActivityType::PrOpened, ActivityType::PrMerged] as $type) {
        resolve(TrackActivity::class)->handle(new TrackActivityDTO(
            externalIdentityId: $identity->id,
            type: $type,
            attributedBy: AttributionMethod::ExternalId,
            occurredAt: CarbonImmutable::now(),
            externalRef: 'github:'.$type->value.':he4rt/api:474',
        ));
    }

    expect(Interaction::query()->count())->toBe(2)
        ->and(Interaction::query()->pluck('external_identity_id')->unique())->toHaveCount(1);
});
