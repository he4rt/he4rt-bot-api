<?php

declare(strict_types=1);

namespace He4rt\Marketing\Database\Factories;

use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkClick;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShortLinkClick> */
final class ShortLinkClickFactory extends Factory
{
    protected $model = ShortLinkClick::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'short_link_id' => ShortLink::factory(),
            'clicked_at' => now(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referer' => null,
            'country_code' => 'BR',
            'device_type' => fake()->randomElement(['desktop', 'smartphone', 'tablet']),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Mobile Safari']),
            'os' => fake()->randomElement(['Windows', 'Mac', 'Android', 'iOS']),
            'is_bot' => false,
            'bot_name' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'user_id' => null,
        ];
    }

    /** A preview crawler (Discord, WhatsApp, Twitter, Slack). */
    public function bot(string $name = 'Discordbot'): static
    {
        return $this->state([
            'is_bot' => true,
            'bot_name' => $name,
            'user_agent' => $name.'/2.0; (+https://discordapp.com)',
            'device_type' => null,
            'browser' => null,
            'os' => null,
        ]);
    }

    public function fromCampaign(string $source = 'twitter', string $medium = 'post', ?string $campaign = null): static
    {
        return $this->state([
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'referer' => 'https://'.$source.'.com/he4rtdevs/status/1',
        ]);
    }
}
