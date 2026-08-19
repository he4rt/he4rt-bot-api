<?php

declare(strict_types=1);

use He4rt\Events\Event\Models\Event;
use He4rt\Portal\Livewire\UpcomingEventsSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->withoutVite();
    app()->setLocale('pt_BR');
});

it('exibe apenas eventos publicados com data futura, ordenados por início', function (): void {
    Event::factory()->published()->create([
        'title' => 'He4rt Meetup #42',
        'location' => 'Sede He4rt — Sala 1',
    ]);

    Event::factory()->published()->create([
        'title' => 'Workshop: Rust do Zero ao Deploy',
        'location' => 'Online — Discord He4rt',
    ]);

    Event::factory()->create([
        'title' => 'Bootcamp Laravel',
    ]);

    Event::factory()->published()->past()->create([
        'title' => 'Live: PHP 8.5',
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('He4rt Meetup #42')
        ->assertSee('Workshop: Rust do Zero ao Deploy')
        ->assertDontSee('Bootcamp Laravel')
        ->assertDontSee('Live: PHP 8.5');
});

it('ordena os eventos pela data de início', function (): void {
    Event::factory()->published()->create([
        'title' => 'Workshop: Docker para Desenvolvedores',
        'starts_at' => now()->addDays(20)->setTime(9, 0),
        'ends_at' => now()->addDays(20)->setTime(18, 0),
    ]);

    Event::factory()->published()->create([
        'title' => 'He4rt Conf 2026',
        'starts_at' => now()->addDays(5)->setTime(9, 0),
        'ends_at' => now()->addDays(5)->setTime(18, 0),
    ]);

    $component = livewire(UpcomingEventsSection::class);

    /** @var Collection<int, array{event: Event}> $events */
    $events = $component->get('upcomingEvents');

    expect($events)->toHaveCount(2)
        ->and($events->pluck('event.title')->values()->all())->toBe(['He4rt Conf 2026', 'Workshop: Docker para Desenvolvedores']);
});

it('renderiza a mensagem de fallback quando não há eventos futuros', function (): void {
    Event::factory()->published()->past()->create();

    livewire(UpcomingEventsSection::class)
        ->assertSee('Nenhum evento agendado no momento')
        ->assertDontSee('events-carousel');
});

it('renderiza badge de data e placeholder He4rt quando o evento não tem capa', function (): void {
    Event::factory()->published()->create([
        'title' => 'Workshop: Rust do Zero ao Deploy',
        'location' => null,
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Workshop: Rust do Zero ao Deploy')
        ->assertSee('logo.svg', escape: false)
        ->assertSee('Online')
        ->assertSee('id="agenda"', escape: false);
});

it('exibe badge Presencial quando o evento tem local presencial', function (): void {
    Event::factory()->published()->create([
        'title' => 'He4rt Meetup #42',
        'location' => 'Sede He4rt — Sala 1',
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Presencial')
        ->assertDontSee('Online');
});

it('exibe badge Online quando o local é online', function (): void {
    Event::factory()->published()->create([
        'title' => 'Workshop: Rust do Zero ao Deploy',
        'location' => 'Online — Discord He4rt',
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Online')
        ->assertDontSee('Presencial');
});

it('aponta o botão Participar para a página do evento', function (): void {
    $event = Event::factory()->published()->create([
        'title' => 'He4rt Conf 2026',
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('href="'.url('/app/events/'.$event->id).'"', escape: false)
        ->assertSee('Participar');
});

it('inclui dados estruturados JSON-LD na home quando existem eventos', function (): void {
    Event::factory()->published()->create([
        'title' => 'He4rt Meetup #42',
    ]);

    get('/')
        ->assertOk()
        ->assertSee('application/ld+json', escape: false)
        ->assertSee('He4rt Meetup #42');
});

it('renderiza a capa do evento no card com atributos de SEO', function (): void {
    Storage::fake('public');

    $event = Event::factory()->published()->create([
        'title' => 'Hacktoberfest 2026',
    ]);

    $event->addMediaFromString('fake cover bytes')
        ->usingFileName('cover.png')
        ->usingName('Hacktoberfest 2026')
        ->toMediaCollection('cover');

    livewire(UpcomingEventsSection::class)
        ->assertSee('Capa do evento Hacktoberfest 2026')
        ->assertSee('loading="lazy"', escape: false)
        ->assertSee('fetchpriority="low"', escape: false)
        ->assertSee('<h2', escape: false)
        ->assertSee('"image"', escape: false)
        ->assertSee('"url"', escape: false);
});
