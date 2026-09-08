<?php

declare(strict_types=1);

namespace He4rt\Contents\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

enum ContentProvider: string implements HasColor, HasDescription, HasLabel
{
    case DevTo = 'devto';

    public static function tryFromIdentityProvider(IdentityProvider $provider): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->toIdentityProvider() === $provider) {
                return $case;
            }
        }

        return null;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DevTo => 'Dev.to',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::DevTo => Color::Gray,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DevTo => 'Artigos publicados no Dev.to pela organizacao e por membros conectados.',
        };
    }

    public function toIdentityProvider(): ?IdentityProvider
    {
        foreach (IdentityProvider::cases() as $identityProvider) {
            if ($identityProvider->value === $this->value) {
                return $identityProvider;
            }
        }

        return null;
    }
}
