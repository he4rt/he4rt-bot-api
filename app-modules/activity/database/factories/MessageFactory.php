<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
final class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * `provider_message_id` tem índice único e dez mil valores possíveis não bastam:
     * a colisão aparecia como flake em qualquer teste que criasse mensagens demais.
     */
    private static int $sequence = 0;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'external_identity_id' => ExternalIdentity::factory(),
            'provider_message_id' => ++self::$sequence,
            'channel_id' => fake()->randomNumber(4),
            'content' => fake()->sentence(),
            'sent_at' => now(),
            'obtained_experience' => fake()->randomNumber(2),
        ];
    }
}
