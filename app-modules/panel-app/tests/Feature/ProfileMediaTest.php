<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Enums\ProfileImage;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Pages\ProfilePage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Storage::fake('public');

    Cache::flush();

    // A pagina renderiza os selects de localizacao, que consultam a API de geo.
    Http::fake([
        'world.bmbc.cloud/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('app'));
});

test('cover upload generates the normalized webp conversion', function (): void {
    $cover = ProfileImage::Cover;

    livewire(ProfilePage::class)
        ->callAction('editCover', [
            $cover->value => UploadedFile::fake()->image('capa.jpg', $cover->width(), $cover->height()),
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $media = $this->user->refresh()->getFirstMedia($cover->value);

    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion($cover->value))->toBeTrue()
        ->and($this->user->imageUrl($cover))->toContain('.webp');
});

test('cover upload accepts the minimum size and lets the conversion upscale it', function (): void {
    $cover = ProfileImage::Cover;

    livewire(ProfilePage::class)
        ->callAction('editCover', [
            $cover->value => UploadedFile::fake()->image('capa.jpg', $cover->minWidth(), $cover->minHeight()),
        ])
        ->assertHasNoActionErrors();

    expect($this->user->refresh()->getFirstMedia($cover->value))->not->toBeNull();
});

test('cover upload accepts a crop that lands a pixel off the exact ratio', function (): void {
    // O editor recorta no canvas e arredonda para pixel inteiro, entao o
    // arquivo quase nunca sai na proporcao exata. A validacao nao pode exigir
    // exatidao: quem enquadra e a conversion. Ver issue #458.
    $cover = ProfileImage::Cover;

    livewire(ProfilePage::class)
        ->callAction('editCover', [
            $cover->value => UploadedFile::fake()->image('capa.jpg', $cover->width() + 1, $cover->height()),
        ])
        ->assertHasNoActionErrors();

    expect($this->user->refresh()->getFirstMedia($cover->value))->not->toBeNull();
});

test('cover upload rejects an image below the minimum width', function (): void {
    $cover = ProfileImage::Cover;

    livewire(ProfilePage::class)
        ->callAction('editCover', [
            $cover->value => UploadedFile::fake()->image('capa.jpg', $cover->minWidth() - 1, $cover->minHeight()),
        ])
        ->assertHasActionErrors([$cover->value]);

    expect($this->user->refresh()->getMedia($cover->value))->toBeEmpty();
});

test('avatar upload rejects an image below the minimum size', function (): void {
    $avatar = ProfileImage::Avatar;

    livewire(ProfilePage::class)
        ->callAction('editAvatar', [
            $avatar->value => UploadedFile::fake()->image('foto.jpg', $avatar->minWidth() - 1, $avatar->minHeight() - 1),
        ])
        ->assertHasActionErrors([$avatar->value]);

    expect($this->user->refresh()->getMedia($avatar->value))->toBeEmpty();
});

test('cover upload keeps a gif untouched so the animation survives', function (): void {
    // Converter achataria a animacao: o GD le so o primeiro quadro. Quem
    // enquadra o GIF e o object-cover do header. Ver issue #458.
    $cover = ProfileImage::Cover;

    livewire(ProfilePage::class)
        ->callAction('editCover', [
            $cover->value => UploadedFile::fake()->image('animado.gif', $cover->width(), $cover->height()),
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $media = $this->user->refresh()->getFirstMedia($cover->value);

    expect($media)->not->toBeNull()
        ->and($media->mime_type)->toBe('image/gif')
        ->and($media->hasGeneratedConversion($cover->value))->toBeFalse()
        ->and($this->user->imageUrl($cover))->toEndWith('.gif');
});

test('an unsupported format is rejected with a message that names the accepted ones', function (): void {
    $cover = ProfileImage::Cover;

    $component = livewire(ProfilePage::class)
        ->callAction('editCover', [
            $cover->value => UploadedFile::fake()->create('capa.bmp', 64, 'image/bmp'),
        ])
        ->assertHasActionErrors([$cover->value]);

    $errors = $component->instance()->getErrorBag()->all();

    expect(implode(' ', $errors))->toContain(ProfileImage::formatLabels())
        ->and($this->user->refresh()->getMedia($cover->value))->toBeEmpty();
});

test('a new upload replaces the previous image instead of piling up', function (): void {
    $cover = ProfileImage::Cover;

    $page = livewire(ProfilePage::class);

    foreach (['primeira.jpg', 'segunda.jpg'] as $fileName) {
        $page->callAction('editCover', [
            $cover->value => UploadedFile::fake()->image($fileName, $cover->width(), $cover->height()),
        ])->assertHasNoActionErrors();
    }

    expect($this->user->refresh()->getMedia($cover->value))->toHaveCount(1);
});

test('a gif over the ceiling never reaches the collection', function (): void {
    // Qual mensagem aparece depende de qual teto e menor no ambiente, e a regra
    // em si esta coberta em UnconvertedImageSizeTest. Aqui o que importa e que
    // o arquivo nao entra.
    $cover = ProfileImage::Cover;

    $gif = UploadedFile::fake()->create('pesado.gif', ProfileImage::unconvertedMaxKilobytes() + 512, 'image/gif');

    livewire(ProfilePage::class)
        ->callAction('editCover', [$cover->value => $gif])
        ->assertHasActionErrors([$cover->value]);

    expect($this->user->refresh()->getMedia($cover->value))->toBeEmpty();
});

test('uploading a gif chains straight into the framing step', function (): void {
    // Sem conversao a imagem vai inteira para a tela: o enquadramento deixa de
    // ser opcional, entao o modal seguinte abre sozinho.
    $cover = ProfileImage::Cover;

    $component = livewire(ProfilePage::class)
        ->callAction('editCover', [
            $cover->value => UploadedFile::fake()->image('animado.gif', $cover->width(), $cover->height()),
        ])
        ->assertHasNoActionErrors();

    expect($component->instance()->getMountedActions())->toHaveCount(1)
        ->and($component->instance()->getMountedActions()[0]->getName())->toBe('adjustCover');
});

test('uploading a jpg does not ask for framing, the conversion already crops it', function (): void {
    $cover = ProfileImage::Cover;

    $component = livewire(ProfilePage::class)
        ->callAction('editCover', [
            $cover->value => UploadedFile::fake()->image('capa.jpg', $cover->width(), $cover->height()),
        ])
        ->assertHasNoActionErrors();

    expect($component->instance()->getMountedActions())->toBeEmpty();
});

test('the cover framing is saved on the media and defaults to the centre', function (): void {
    $cover = ProfileImage::Cover;

    $this->user->putImage($cover, UploadedFile::fake()->image('animado.gif', $cover->width(), $cover->height()));

    expect($this->user->refresh()->imageFocalY($cover))->toBe(ProfileImage::DEFAULT_FOCAL_Y);

    livewire(ProfilePage::class)
        ->callAction('adjustCover', ['focal_y' => 80])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($this->user->refresh()->imageFocalY($cover))->toBe(80);
});

test('the framing is clamped to the image bounds', function (): void {
    $cover = ProfileImage::Cover;

    $this->user->putImage($cover, UploadedFile::fake()->image('capa.jpg', $cover->width(), $cover->height()));

    $this->user->setImageFocalY($cover, 140);

    expect($this->user->refresh()->imageFocalY($cover))->toBe(100);

    $this->user->setImageFocalY($cover, -20);

    expect($this->user->refresh()->imageFocalY($cover))->toBe(0);
});

test('removing the cover clears the collection', function (): void {
    $cover = ProfileImage::Cover;

    $this->user->putImage($cover, UploadedFile::fake()->image('capa.jpg', $cover->width(), $cover->height()));

    expect($this->user->refresh()->getMedia($cover->value))->toHaveCount(1);

    livewire(ProfilePage::class)->call('removeCover');

    expect($this->user->refresh()->getMedia($cover->value))->toBeEmpty();
});

test('imageUrl falls back to the original while the conversion does not exist', function (): void {
    $cover = ProfileImage::Cover;

    $this->user->putImage($cover, UploadedFile::fake()->image('capa.jpg', $cover->width(), $cover->height()));

    $media = $this->user->refresh()->getFirstMedia($cover->value);
    $media->generated_conversions = [];
    $media->save();

    expect($this->user->refresh()->imageUrl($cover))
        ->toBe($media->refresh()->getUrl())
        ->not->toContain('-'.$cover->value.'.webp');
});
