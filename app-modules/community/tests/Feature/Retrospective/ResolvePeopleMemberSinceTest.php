<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\ResolvePeople;
use He4rt\Community\Retrospective\Contracts\MembershipDates;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

/**
 * @param  array<string, CarbonImmutable>  $dates
 */
function fakeMembershipDates(array $dates): void
{
    app()->instance(MembershipDates::class, new readonly class($dates) implements MembershipDates
    {
        /**
         * @param  array<string, CarbonImmutable>  $dates
         */
        public function __construct(private array $dates) {}

        public function execute(array $identityIds): array
        {
            return array_intersect_key($this->dates, array_flip($identityIds));
        }
    });
}

function linkDiscord(User $user): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
    ]);
}

it('usa a entrada no Discord quando ela é mais antiga que a conta do site', function (): void {
    $user = User::factory()->create(['created_at' => '2024-05-10 12:00:00']);
    $identity = linkDiscord($user);

    fakeMembershipDates([$identity->id => CarbonImmutable::parse('2020-03-01 00:00:00')]);

    $person = resolve(ResolvePeople::class)->execute([$user->id])[$user->id];

    expect($person->memberSince?->toDateString())->toBe('2020-03-01');
});

it('cai para o created_at do usuário quando ele é anterior ao Discord', function (): void {
    $user = User::factory()->create(['created_at' => '2019-01-15 12:00:00']);
    $identity = linkDiscord($user);

    fakeMembershipDates([$identity->id => CarbonImmutable::parse('2022-08-01 00:00:00')]);

    $person = resolve(ResolvePeople::class)->execute([$user->id])[$user->id];

    expect($person->memberSince?->toDateString())->toBe('2019-01-15');
});

it('sem data do Discord, o created_at responde sozinho', function (): void {
    $user = User::factory()->create(['created_at' => '2023-11-02 12:00:00']);
    linkDiscord($user);

    fakeMembershipDates([]);

    $person = resolve(ResolvePeople::class)->execute([$user->id])[$user->id];

    expect($person->memberSince?->toDateString())->toBe('2023-11-02');
});
