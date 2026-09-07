<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Retrospective\DiscordSource;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

it('não conta mensagens do chat da live na retrospectiva do Discord', function (): void {
    $discordIdentity = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Discord]);
    $liveIdentity = ExternalIdentity::factory()->create(['provider' => IdentityProvider::He4rtLives]);

    Message::factory()->for($discordIdentity, 'provider')->create(['sent_at' => now()]);
    Message::factory()->for($liveIdentity, 'provider')->create(['sent_at' => now()]);

    $since = CarbonImmutable::now()->subDay();
    $until = CarbonImmutable::now()->addDay();

    $result = new DiscordSource()->collect(
        Period::of($since, $until),
        new SourceFilters(),
    );

    $messages = [];
    foreach ($result->slides as $slide) {
        if ($slide->kind() === 'discord.messages') {
            $messages = $slide->toArray();
        }
    }

    expect($messages['total'] ?? 0)->toBe(1);
});
