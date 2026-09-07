<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Enums\NavigationGroup as NavGroup;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    config(['he4rt.admins' => 'danielhe4rt']);

    actingAs(User::factory()->create(['username' => 'danielhe4rt']));

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * Grupo da navegação padrão do painel pelo label. `null` procura o bloco sem
 * label, onde ficam os itens de topo (dashboard e clusters).
 */
function adminNavigationGroup(?string $label): NavigationGroup
{
    foreach (Filament::getPanel('admin')->getNavigation() as $group) {
        if ($group->getLabel() === $label) {
            return $group;
        }
    }

    throw new RuntimeException(sprintf('Grupo de navegação [%s] não encontrado.', $label ?? 'sem label'));
}

/**
 * @return array<int, string>
 */
function navigationItemLabels(NavigationGroup $group): array
{
    return array_map(
        static fn (NavigationItem $item): string => $item->getLabel(),
        $group->getItems(),
    );
}

test('a navegação padrão expõe o grupo Pessoas', function (): void {
    $people = adminNavigationGroup(NavGroup::People->getLabel());

    expect($people->getLabel())->toBe(NavGroup::People->getLabel());
});

test('o grupo Pessoas reúne as quatro telas da aba', function (): void {
    $people = adminNavigationGroup(NavGroup::People->getLabel());

    expect(navigationItemLabels($people))->toHaveCount(4)
        ->and(navigationItemLabels($people))->toEqualCanonicalizing([
            'Users',
            'External Identities',
            'Profiles',
            'Skills',
        ]);
});

test('a navegação padrão expõe o grupo Conteúdo com o que a comunidade publica', function (): void {
    $content = adminNavigationGroup(NavGroup::Content->getLabel());

    expect(navigationItemLabels($content))->toBe(['Artigos', 'Contribuições', 'Retrospectivas']);
});

test('os clusters seguem como itens de topo, fora de grupo', function (): void {
    $labels = navigationItemLabels(adminNavigationGroup(label: null));

    expect($labels)->toContain('Dashboard')
        ->toContain('Twitch')
        ->toContain('GitHub')
        ->toContain('Discord');
});

test('identidades externas não aparece mais solto no topo', function (): void {
    $labels = navigationItemLabels(adminNavigationGroup(label: null));

    expect($labels)->not->toContain('External Identities');
});

test('o resource de lives aparece como item de topo, fora de grupo', function (): void {
    $labels = navigationItemLabels(adminNavigationGroup(label: null));

    expect($labels)->toContain('Lives');
});
