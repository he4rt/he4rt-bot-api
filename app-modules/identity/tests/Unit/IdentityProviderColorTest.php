<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

/**
 * O BadgeComponent do Filament indexa a paleta por tonalidade (50, 400, 600…).
 * Uma cor declarada como hex solto renderiza a listagem inteira com
 * "Undefined array key 50", e só aparece quando aquele provider cai na tela.
 */
test('todo provider expõe a paleta completa de tonalidades', function (IdentityProvider $provider): void {
    expect($provider->getColor())
        ->toBeArray()
        ->toHaveKeys([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]);
})->with(IdentityProvider::cases());
