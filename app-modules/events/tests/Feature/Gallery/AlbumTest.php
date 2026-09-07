<?php

declare(strict_types=1);

use He4rt\Events\Event\Models\Event;
use He4rt\Events\Gallery\Actions\CuratePhoto;
use He4rt\Events\Gallery\DTOs\CuratePhotoData;
use He4rt\Events\Gallery\DTOs\Photo;
use He4rt\Events\Gallery\Enums\PhotoConversion;
use He4rt\Events\Gallery\Models\Album;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;

beforeEach(function (): void {
    Storage::fake('public');
    runMediaConversionsInline();
});

test('when a photo is added, then thumb and large webp conversions are generated', function (): void {
    $album = Album::factory()->create();

    $album
        ->addMedia(UploadedFile::fake()->image('foto.jpg', 1_600, 1_200))
        ->toMediaCollection(Album::PHOTOS);

    $media = $album->getFirstMedia(Album::PHOTOS);

    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion(PhotoConversion::Thumb->value))->toBeTrue()
        ->and($media->hasGeneratedConversion(PhotoConversion::Large->value))->toBeTrue()
        ->and($media->getUrl(PhotoConversion::Thumb->value))->toEndWith('.webp');
});

test('when a gif is added, then the photos collection rejects it', function (): void {
    $album = Album::factory()->create();

    $album
        ->addMedia(UploadedFile::fake()->image('animada.gif'))
        ->toMediaCollection(Album::PHOTOS);
})->throws(FileUnacceptableForCollection::class);

test('when nobody flagged a highlight, then the first three photos represent the album', function (): void {
    $album = Album::factory()->withPhotos(5)->create();

    $highlights = $album->highlights();

    expect($highlights)->toHaveCount(Album::CARD_HIGHLIGHTS)
        ->and($highlights->pluck('name')->all())->toBe(['foto-1', 'foto-2', 'foto-3']);
});

test('when photos are flagged as highlight, then only those represent the album', function (): void {
    $album = Album::factory()->withPhotos(5)->create();
    $photos = $album->getMedia(Album::PHOTOS);

    $curate = resolve(CuratePhoto::class);
    $curate->handle($photos[3], new CuratePhotoData(caption: 'Lightning talks', highlight: true));
    $curate->handle($photos[4], new CuratePhotoData(caption: null, highlight: true));

    $highlights = $album->fresh()->highlights();

    expect($highlights->pluck('name')->all())->toBe(['foto-4', 'foto-5'])
        ->and($album->fresh()->cover()?->name)->toBe('foto-4');
});

test('when curating with a blank caption, then the caption is stored as null', function (): void {
    $album = Album::factory()->withPhotos(1)->create();
    $media = $album->getFirstMedia(Album::PHOTOS);

    resolve(CuratePhoto::class)->handle($media, new CuratePhotoData(caption: '   ', highlight: false));

    $photo = Photo::fromMedia($media->fresh());

    expect($photo->caption)->toBeNull()
        ->and($photo->highlight)->toBeFalse();
});

test('when published_at is in the future, then the published scope excludes the album', function (): void {
    $past = Album::factory()->published()->create();
    Album::factory()->create(['published_at' => now()->addDay()]);
    Album::factory()->create();

    expect(Album::query()->published()->pluck('id')->all())->toBe([$past->id]);
});

test('when an album has no photos, then the withPhotos scope excludes it', function (): void {
    $withPhotos = Album::factory()->published()->withPhotos(1)->create();
    Album::factory()->published()->create();

    expect(Album::query()->published()->withPhotos()->pluck('id')->all())->toBe([$withPhotos->id]);
});

test('when the linked event is deleted, then the album survives without an event', function (): void {
    $event = Event::factory()->create();
    $album = Album::factory()->forEvent($event)->create();

    $event->delete();

    expect($album->fresh()->event_id)->toBeNull();
});
