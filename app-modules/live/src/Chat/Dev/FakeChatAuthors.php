<?php

declare(strict_types=1);

namespace He4rt\Live\Chat\Dev;

use He4rt\Identity\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Pool de usuários e frases fake para simular tráfego de chat em ambiente de dev. */
final readonly class FakeChatAuthors
{
    private const int POOL_SIZE = 8;

    /** @var list<string> */
    private const array PHRASES = [
        'kkkkkkkk',
        '🔥🔥🔥',
        'que jogada!',
        'bora bora',
        'primeira vez aqui 👋',
        'top demais',
        'segura a hype',
        'vamo que vamo',
        'boa noite, chat',
        'esse setup é novo?',
    ];

    /** @return Collection<int, User> */
    public function pool(): Collection
    {
        return collect(range(1, self::POOL_SIZE))->map(
            fn (int $n): User => User::query()->firstOrCreate(
                ['email' => "chat-sim-{$n}@dev.local"],
                [
                    'id' => (string) Str::uuid(),
                    'username' => "chat-sim-{$n}",
                    'name' => "Fã da Live {$n}",
                    'password' => Hash::make(Str::random(32)),
                    'is_donator' => false,
                ],
            ),
        );
    }

    public function random(): User
    {
        return $this->pool()->random();
    }

    public function randomPhrase(): string
    {
        return Arr::random(self::PHRASES);
    }
}
