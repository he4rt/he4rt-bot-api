<?php

declare(strict_types=1);

use He4rt\Events\Event\Models\Event;
use He4rt\Events\Gallery\Actions\CuratePhoto;
use He4rt\Events\Gallery\DTOs\CuratePhotoData;
use He4rt\Events\Gallery\Models\Album;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();

    Storage::fake('public');
    runMediaConversionsInline();
});

it('lista os álbuns publicados do mais recente ao mais antigo', function (): void {
    Album::factory()->published()->withPhotos(2)->create([
        'title' => 'Pub de fim de ano',
        'happened_at' => '2025-12-13',
    ]);
    Album::factory()->published()->withPhotos(2)->create([
        'title' => 'Meetup de março',
        'happened_at' => '2025-03-20',
    ]);

    $html = get('/galeria')->assertOk()->getContent();

    expect($html)
        ->toContain('Pub de fim de ano')
        ->toContain('Meetup de março');

    expect(mb_strpos($html, 'Pub de fim de ano'))
        ->toBeLessThan(mb_strpos($html, 'Meetup de março'));
});

it('mostra os destaques e quantas fotos ficaram de fora', function (): void {
    $album = Album::factory()->published()->withPhotos(5)->create();

    resolve(CuratePhoto::class)->handle(
        $album->getMedia(Album::PHOTOS)->last(),
        new CuratePhotoData(caption: 'Brinde final', highlight: true),
    );

    get('/galeria')
        ->assertOk()
        ->assertSee('Brinde final')
        ->assertSee('5 fotos')
        ->assertSee('+4');
});

it('esconde rascunhos e álbuns sem fotos', function (): void {
    Album::factory()->withPhotos(2)->create(['title' => 'Rascunho secreto']);
    Album::factory()->published()->create(['title' => 'Álbum vazio']);
    Album::factory()->published()->withPhotos(1)->create(['title' => 'Álbum visível']);

    get('/galeria')
        ->assertOk()
        ->assertSee('Álbum visível')
        ->assertDontSee('Rascunho secreto')
        ->assertDontSee('Álbum vazio');
});

it('explica quando ainda não há álbuns', function (): void {
    get('/galeria')
        ->assertOk()
        ->assertSee('Ainda não há álbuns por aqui.');
});

it('abre um álbum com todas as fotos e o payload do lightbox', function (): void {
    $event = Event::factory()->create(['title' => 'He4rt Meetup SP']);

    $album = Album::factory()
        ->published()
        ->forEvent($event)
        ->withPhotos(4)
        ->create([
            'slug' => 'meetup-sp-2025',
            'title' => 'Meetup SP 2025',
            'location' => 'São Paulo',
            'description' => 'Primeira edição presencial do ano.',
        ]);

    resolve(CuratePhoto::class)->handle(
        $album->getMedia(Album::PHOTOS)->first(),
        new CuratePhotoData(caption: 'Abertura', highlight: false),
    );

    $html = get('/galeria/meetup-sp-2025')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Meetup SP 2025')
        ->toContain('Primeira edição presencial do ano.')
        ->toContain('He4rt Meetup SP')
        ->toContain('São Paulo')
        ->toContain('4 fotos')
        ->toContain('Abrir foto 4 de 4')
        ->toContain('galleryLightbox(')
        ->toContain('Abertura')
        ->toContain('<meta property="og:image"');
});

it('responde 404 para rascunho, álbum vazio e slug desconhecido', function (): void {
    Album::factory()->withPhotos(1)->create(['slug' => 'rascunho']);
    Album::factory()->published()->create(['slug' => 'vazio']);

    get('/galeria/rascunho')->assertNotFound();
    get('/galeria/vazio')->assertNotFound();
    get('/galeria/nao-existe')->assertNotFound();
});

it('marca a galeria como ativa na navbar do álbum', function (): void {
    Album::factory()->published()->withPhotos(1)->create(['slug' => 'ativo']);

    get('/galeria/ativo')
        ->assertOk()
        ->assertSee('href="/galeria"', escape: false);
});
