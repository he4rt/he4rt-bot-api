<?php

declare(strict_types=1);

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

test('devto maps to identity provider devto', function (): void {
    expect(ContentProvider::DevTo->toIdentityProvider())->toBe(IdentityProvider::DevTo);
});

test('tryFromIdentityProvider resolves devto', function (): void {
    expect(ContentProvider::tryFromIdentityProvider(IdentityProvider::DevTo))->toBe(ContentProvider::DevTo);
});

test('tryFromIdentityProvider returns null for an unmapped provider', function (): void {
    expect(ContentProvider::tryFromIdentityProvider(IdentityProvider::Discord))->toBeNull();
});

test('implements filament contracts', function (): void {
    $provider = ContentProvider::DevTo;

    expect($provider)->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasDescription::class)
        ->and($provider->getLabel())->toBe('Dev.to')
        ->and($provider->getColor())->not->toBeNull()
        ->and($provider->getDescription())->not->toBeNull();
});
