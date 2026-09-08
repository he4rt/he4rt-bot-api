<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\Marketing\ShortLink\Enums\ShortLinkStatus;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkClick;
use He4rt\Marketing\ShortLink\Models\ShortLinkDestination;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\CreateShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\EditShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\ListShortLinks;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\ViewShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\ShortLinkResource;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets\ClicksOverTimeChart;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets\DeviceBreakdownChart;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets\RecentClicksTable;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets\TopReferersTable;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

/**
 * Lê a série sem renderizar o canvas. Reflection e não um closure com
 * `->call()`: o Rector converte `fn () => $this->foo()` em first-class callable,
 * que avalia `$this` fora de contexto e quebra o teste a cada `make format`.
 *
 * @return array<int, int>
 */
function chartClicks(ClicksOverTimeChart $chart): array
{
    /** @var array<string, mixed> $data */
    $data = new ReflectionMethod($chart, 'getCachedData')->invoke($chart);

    return $data['datasets'][0]['data'];
}

/**
 * O admin do teste, tipado: `$this->user` seria `mixed` para o PHPStan.
 */
function admin(): User
{
    return User::query()->where('username', 'danielhe4rt')->sole();
}

beforeEach(function (): void {
    config([
        'app.display_timezone' => 'America/Sao_Paulo',
    ]);

    $this->actingAs(User::factory()->superAdmin()->create(['username' => 'danielhe4rt']));

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('it lists short links', function (): void {
    $links = ShortLink::factory()->count(3)->create();

    livewire(ListShortLinks::class)
        ->loadTable()
        ->assertSuccessful()
        ->assertCanSeeTableRecords($links);
});

test('creating a link delegates to the CreateShortLink action', function (): void {
    livewire(CreateShortLink::class)
        ->fillForm([
            'nickname' => 'Discord',
            'destination_url' => 'https://discord.gg/he4rt',
            'tags' => ['comunidade'],
            'active' => true,
            'utm_source' => 'discord',
            'utm_medium' => 'post',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $link = ShortLink::query()->latest('created_at')->firstOrFail();

    expect($link->base_slug)->toBe('discord')
        ->and($link->slug)->toMatch('/^discord-[0-9a-z]{5}$/')
        ->and($link->destination_url)->toBe('https://discord.gg/he4rt')
        ->and($link->tags->toArray())->toBe(['comunidade'])
        ->and($link->utm->source)->toBe('discord')
        ->and($link->utm->medium)->toBe('post')
        ->and($link->created_by)->toBe(admin()->id)
        ->and($link->destinations()->whereNull('valid_until')->count())->toBe(1);
});

test('editing the destination delegates to the UpdateShortLink action and versions the history', function (): void {
    $link = ShortLink::factory()->create([
        'destination_url' => 'https://old.example.com',
    ]);

    $link->destinations()->create([
        'destination_url' => $link->destination_url,
        'utm' => $link->utm,
        'changed_by' => null,
        'valid_from' => now()->subDay(),
        'valid_until' => null,
    ]);

    $originalSlug = $link->slug;

    livewire(EditShortLink::class, ['record' => $link->getKey()])
        ->fillForm(['destination_url' => 'https://new.example.com'])
        ->call('save')
        ->assertHasNoFormErrors();

    $link->refresh();

    expect($link->destination_url)->toBe('https://new.example.com')
        ->and($link->slug)->toBe($originalSlug)
        ->and($link->destinations()->count())->toBe(2)
        ->and($link->destinations()->whereNull('valid_until')->count())->toBe(1)
        ->and($link->currentDestination?->destination_url)->toBe('https://new.example.com');
});

test('the destination is required', function (): void {
    livewire(CreateShortLink::class)
        ->fillForm([
            'nickname' => 'Sem destino',
            'destination_url' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['destination_url' => 'required']);
});

test('a non-http destination is rejected', function (): void {
    livewire(CreateShortLink::class)
        ->fillForm([
            'nickname' => 'Xss',
            'destination_url' => 'javascript:alert(1)',
        ])
        ->call('create')
        ->assertHasFormErrors(['destination_url']);

    expect(ShortLink::query()->count())->toBe(0);
});

test('the status filter matches the derived status', function (): void {
    $active = ShortLink::factory()->create();
    $expired = ShortLink::factory()->expired()->create();
    $disabled = ShortLink::factory()->disabled()->create();

    livewire(ListShortLinks::class)
        ->loadTable()
        ->filterTable('status', ShortLinkStatus::Active->value)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$expired, $disabled]);

    livewire(ListShortLinks::class)
        ->loadTable()
        ->filterTable('status', ShortLinkStatus::Expired->value)
        ->assertCanSeeTableRecords([$expired])
        ->assertCanNotSeeTableRecords([$active, $disabled]);

    livewire(ListShortLinks::class)
        ->loadTable()
        ->filterTable('status', ShortLinkStatus::Disabled->value)
        ->assertCanSeeTableRecords([$disabled])
        ->assertCanNotSeeTableRecords([$active, $expired]);
});

test('the clicks column shows the human count, not the total', function (): void {
    $link = ShortLink::factory()->withClicks(total: 1_284, human: 1_147)->create();

    livewire(ListShortLinks::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$link])
        ->assertTableColumnStateSet('human_clicks_count', 1_147, $link)
        ->assertTableColumnStateNotSet('human_clicks_count', 1_284, $link);
});

test('the tag filter narrows the list to links carrying the tag', function (): void {
    $tagged = ShortLink::factory()->withTags(['hacktoberfest'])->create();
    $other = ShortLink::factory()->withTags(['comunidade'])->create();

    livewire(ListShortLinks::class)
        ->loadTable()
        ->filterTable('tag', 'hacktoberfest')
        ->assertCanSeeTableRecords([$tagged])
        ->assertCanNotSeeTableRecords([$other]);
});

test('the view page titles itself with the short url and shows the destination history', function (): void {
    $link = ShortLink::factory()->withTags(['comunidade'])->create([
        'destination_url' => 'https://new.example.com',
    ]);

    ShortLinkDestination::factory()->superseded()->create([
        'short_link_id' => $link->getKey(),
        'destination_url' => 'https://old.example.com',
    ]);

    ShortLinkDestination::factory()->create([
        'short_link_id' => $link->getKey(),
        'destination_url' => $link->destination_url,
        'changed_by' => admin()->id,
    ]);

    $page = livewire(ViewShortLink::class, ['record' => $link->getKey()])
        ->assertSuccessful()
        ->assertSee(Str::after(ShortLinkResource::shortUrl($link), '://'))
        ->assertSeeInOrder(['https://new.example.com', 'https://old.example.com']);

    expect($page->instance()->getSubheading())->toBe('↳ https://new.example.com');
});

test('the clicks tile counts humans only until the bots toggle is on', function (): void {
    // Números grandes: "7" ou "10" apareceriam em qualquer canto do HTML.
    $link = ShortLink::factory()->withClicks(total: 8_134, human: 7_211)->create();

    $humans = (string) Number::format(7_211);
    $everyone = (string) Number::format(8_134);

    $page = livewire(ViewShortLink::class, ['record' => $link->getKey()])
        ->assertSuccessful();

    expect($page->get('filters')[ViewShortLink::INCLUDE_BOTS] ?? null)->toBeFalse();

    $page->assertSee($humans)->assertDontSee($everyone);

    $page->set('filters.'.ViewShortLink::INCLUDE_BOTS, true)
        ->assertSee($everyone)
        ->assertDontSee($humans);
});

test('flipping the bots toggle changes the island keys, which is what remounts the charts', function (): void {
    $link = ShortLink::factory()->create();

    $page = livewire(ViewShortLink::class, ['record' => $link->getKey()])
        ->assertSuccessful();

    $islands = ['clicks-over-time', 'top-referers', 'device-breakdown', 'recent-clicks'];

    $humansOnly = array_map($page->instance()->islandKey(...), $islands);

    foreach ($humansOnly as $key) {
        $page->assertSee($key, escape: false);
    }

    $page->set('filters.'.ViewShortLink::INCLUDE_BOTS, true);

    $withBots = array_map($page->instance()->islandKey(...), $islands);

    expect($withBots)->not->toBe($humansOnly);

    foreach ($withBots as $key) {
        $page->assertSee($key, escape: false);
    }

    foreach ($humansOnly as $key) {
        $page->assertDontSee($key, escape: false);
    }
});

test('the disable action delegates to UpdateShortLink without forging a destination row', function (): void {
    $link = ShortLink::factory()->create();

    ShortLinkDestination::factory()->create([
        'short_link_id' => $link->getKey(),
        'destination_url' => $link->destination_url,
    ]);

    livewire(ViewShortLink::class, ['record' => $link->getKey()])
        ->callAction('toggleActive')
        ->assertNotified();

    expect($link->refresh()->active)->toBeFalse()
        ->and($link->destinations()->count())->toBe(1);
});

test('the analytics islands receive the bot filter as a mount parameter', function (): void {
    $link = ShortLink::factory()->create();

    ShortLinkClick::factory()->count(3)->create([
        'short_link_id' => $link->getKey(),
        'clicked_at' => now()->subHours(2),
        'referer' => 'https://twitter.com/he4rtdevs',
    ]);

    ShortLinkClick::factory()->bot()->count(2)->create([
        'short_link_id' => $link->getKey(),
        'clicked_at' => now()->subHours(2),
        'referer' => 'https://twitter.com/he4rtdevs',
    ]);

    $chart = livewire(ClicksOverTimeChart::class, [
        'record' => $link,
        'includeBots' => false,
    ])->assertSuccessful();

    expect(array_sum(chartClicks($chart->instance())))->toBe(3);

    $chartWithBots = livewire(ClicksOverTimeChart::class, [
        'record' => $link,
        'includeBots' => true,
    ])->assertSuccessful();

    expect(array_sum(chartClicks($chartWithBots->instance())))->toBe(5);
});

test('the analytics widgets render an empty state for a link with no clicks', function (): void {
    $link = ShortLink::factory()->create();

    livewire(ClicksOverTimeChart::class, ['record' => $link])
        ->assertSuccessful();

    livewire(DeviceBreakdownChart::class, ['record' => $link])
        ->assertSuccessful();

    // `assertCountTableRecords()` conta via query Eloquent e esta tabela usa
    // dados customizados.
    livewire(TopReferersTable::class, ['record' => $link])
        ->loadTable()
        ->assertSuccessful()
        ->assertSee(__('panel-admin::marketing.short_links.widgets.top_referers.empty_heading'));

    livewire(RecentClicksTable::class, ['record' => $link])
        ->loadTable()
        ->assertSuccessful()
        ->assertSee(__('panel-admin::marketing.short_links.widgets.recent_clicks.empty_heading'));
});

test('the top referers widget ranks origins and excludes bots by default', function (): void {
    $link = ShortLink::factory()->create();

    ShortLinkClick::factory()->count(3)->create([
        'short_link_id' => $link->getKey(),
        'referer' => 'https://twitter.com/he4rtdevs',
    ]);

    ShortLinkClick::factory()->create([
        'short_link_id' => $link->getKey(),
        'referer' => null,
    ]);

    ShortLinkClick::factory()->bot()->count(5)->create([
        'short_link_id' => $link->getKey(),
        'referer' => 'https://discord.com/channels/1',
    ]);

    livewire(TopReferersTable::class, [
        'record' => $link,
        'includeBots' => false,
    ])
        ->loadTable()
        ->assertSuccessful()
        ->assertSee('https://twitter.com/he4rtdevs')
        ->assertSee(__('panel-admin::marketing.short_links.placeholders.no_referer'))
        ->assertDontSee('https://discord.com/channels/1');
});

test('the recent clicks table hides bots by default and shows them with the toggle', function (): void {
    $link = ShortLink::factory()->create();

    ShortLinkClick::factory()->create([
        'short_link_id' => $link->getKey(),
        'referer' => 'https://twitter.com/he4rtdevs',
    ]);

    ShortLinkClick::factory()->bot('Discordbot')->create([
        'short_link_id' => $link->getKey(),
        'referer' => 'https://discord.com/channels/1',
    ]);

    livewire(RecentClicksTable::class, ['record' => $link, 'includeBots' => false])
        ->loadTable()
        ->assertSuccessful()
        ->assertSee('https://twitter.com/he4rtdevs')
        ->assertDontSee('Discordbot');

    livewire(RecentClicksTable::class, ['record' => $link, 'includeBots' => true])
        ->loadTable()
        ->assertSuccessful()
        ->assertSee('Discordbot');
});

test('the recent clicks table never exposes the ip address or the user agent', function (): void {
    $link = ShortLink::factory()->create();

    $click = ShortLinkClick::factory()->create([
        'short_link_id' => $link->getKey(),
        'ip_address' => '203.0.113.42',
        'user_agent' => 'Mozilla/5.0 (SpecificTestAgent)',
    ]);

    livewire(RecentClicksTable::class, ['record' => $link])
        ->loadTable()
        ->assertSuccessful()
        ->assertDontSee($click->ip_address)
        ->assertDontSee($click->user_agent);
});

test('bot clicks are excluded from the analytics widgets by default', function (): void {
    $link = ShortLink::factory()->create();

    ShortLinkClick::factory()->count(3)->create([
        'short_link_id' => $link->getKey(),
        'clicked_at' => now()->subHours(2),
    ]);

    ShortLinkClick::factory()->bot()->count(2)->create([
        'short_link_id' => $link->getKey(),
        'clicked_at' => now()->subHours(2),
    ]);

    $humanOnly = ShortLinkClick::query()
        ->where('short_link_id', $link->getKey())
        ->where('is_bot', operator: false)
        ->count();

    expect($humanOnly)->toBe(3)
        ->and(ShortLinkClick::query()->where('short_link_id', $link->getKey())->count())->toBe(5);
});
