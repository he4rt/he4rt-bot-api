<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Events\Event\Models\Event;
use He4rt\Events\Gallery\DTOs\Photo;
use He4rt\Events\Gallery\Models\Album;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Albums\Pages\CreateAlbum;
use He4rt\PanelAdmin\Filament\Resources\Albums\Pages\EditAlbum;
use He4rt\PanelAdmin\Filament\Resources\Albums\Pages\ListAlbums;
use He4rt\PanelAdmin\Filament\Resources\Albums\RelationManagers\PhotosRelationManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config([
        'app.display_timezone' => 'America/Sao_Paulo',
    ]);

    Storage::fake('public');
    runMediaConversionsInline();

    $this->admin = User::factory()->superAdmin()->create();

    $this->actingAs($this->admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/** @return array<string, mixed> */
function albumFormData(array $overrides = []): array
{
    return [
        'title' => 'Pub #2',
        'slug' => 'pub-2',
        'location' => 'Curitiba',
        'happened_at' => '2025-08-15',
        'description' => 'Mesa redonda sobre carreira e networking.',
        ...$overrides,
    ];
}

test('a listagem carrega para admin com os álbuns existentes', function (): void {
    $albums = Album::factory()->count(3)->create();

    livewire(ListAlbums::class)
        ->loadTable()
        ->assertOk()
        ->assertCanSeeTableRecords($albums);
});

test('o filtro de publicados esconde os rascunhos', function (): void {
    $published = Album::factory()->published()->create();
    $draft = Album::factory()->create();

    livewire(ListAlbums::class)
        ->loadTable()
        ->filterTable('published', true)
        ->assertCanSeeTableRecords([$published])
        ->assertCanNotSeeTableRecords([$draft]);
});

test('o filtro de publicados esconde os agendados para o futuro', function (): void {
    $published = Album::factory()->published()->create();
    $scheduled = Album::factory()->scheduled()->create();

    livewire(ListAlbums::class)
        ->loadTable()
        ->filterTable('published', true)
        ->assertCanSeeTableRecords([$published])
        ->assertCanNotSeeTableRecords([$scheduled]);
});

test('cria um álbum com fotos em lote na collection de fotos', function (): void {
    livewire(CreateAlbum::class)
        ->fillForm(albumFormData([
            'photos' => [
                UploadedFile::fake()->image('abertura.jpg', 1_600, 1_200),
                UploadedFile::fake()->image('networking.jpg', 1_600, 1_200),
            ],
        ]))
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $album = Album::query()->where('slug', 'pub-2')->sole();

    expect($album->getMedia(Album::PHOTOS))->toHaveCount(2)
        ->and($album->published_at)->toBeNull();
});

test('valida o formulário de criação', function (array $data, array $errors): void {
    livewire(CreateAlbum::class)
        ->fillForm(albumFormData($data))
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNoRedirect();
})->with([
    'título obrigatório' => [['title' => null], ['title' => 'required']],
    'título com no máximo 200 caracteres' => [['title' => str_repeat('a', 201)], ['title' => 'max']],
    'slug obrigatório' => [['slug' => null], ['slug' => 'required']],
    'slug só com letras, números e traços' => [['slug' => 'pub 2!'], ['slug' => 'alpha_dash']],
    'data obrigatória' => [['happened_at' => null], ['happened_at' => 'required']],
]);

test('rejeita slug repetido', function (): void {
    Album::factory()->create(['slug' => 'pub-2']);

    livewire(CreateAlbum::class)
        ->fillForm(albumFormData())
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

test('escolher um evento preenche local e data quando estão vazios', function (): void {
    $event = Event::factory()->create([
        'location' => 'São Paulo',
        'starts_at' => '2025-03-20 19:00:00',
        'ends_at' => '2025-03-20 22:00:00',
    ]);

    livewire(CreateAlbum::class)
        ->fillForm(['title' => 'Pub #1', 'location' => null, 'happened_at' => null])
        ->fillForm(['event_id' => $event->id])
        ->assertSchemaStateSet([
            'location' => 'São Paulo',
            'happened_at' => '2025-03-20',
        ]);
});

test('edita o título de um álbum', function (): void {
    $album = Album::factory()->create();

    livewire(EditAlbum::class, ['record' => $album->getRouteKey()])
        ->fillForm(['title' => 'Confra de fim de ano'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($album->fresh()->title)->toBe('Confra de fim de ano');
});

test('a aba de fotos lista só as fotos do álbum', function (): void {
    $album = Album::factory()->withPhotos(2)->create();

    livewire(PhotosRelationManager::class, [
        'ownerRecord' => $album,
        'pageClass' => EditAlbum::class,
    ])
        ->loadTable()
        ->assertOk()
        ->assertCanSeeTableRecords($album->getMedia(Album::PHOTOS));
});

test('marcar destaque e legenda grava nas propriedades da foto', function (): void {
    $album = Album::factory()->withPhotos(2)->create();
    $photo = $album->getMedia(Album::PHOTOS)->last();

    livewire(PhotosRelationManager::class, [
        'ownerRecord' => $album,
        'pageClass' => EditAlbum::class,
    ])
        ->call('updateTableColumnState', 'highlight', $photo->getKey(), true)
        ->call('updateTableColumnState', 'caption', $photo->getKey(), 'Lightning talks');

    $curated = Photo::fromMedia($photo->fresh());

    expect($curated->highlight)->toBeTrue()
        ->and($curated->caption)->toBe('Lightning talks')
        ->and($album->fresh()->cover()?->getKey())->toBe($photo->getKey());
});
