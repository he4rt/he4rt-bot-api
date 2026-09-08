<?php

declare(strict_types=1);

use He4rt\Identity\User\Enums\ProfileImage;

test('height is always derived from the aspect ratio', function (ProfileImage $image): void {
    [$ratioWidth, $ratioHeight] = explode(':', $image->aspectRatio());

    expect($image->height())->toBe(intdiv($image->width() * (int) $ratioHeight, (int) $ratioWidth))
        ->and($image->minHeight())->toBe(intdiv($image->minWidth() * (int) $ratioHeight, (int) $ratioWidth));
})->with(ProfileImage::cases());

test('the minimum is never bigger than the recommended size', function (ProfileImage $image): void {
    expect($image->minWidth())->toBeLessThanOrEqual($image->width())
        ->and($image->minHeight())->toBeLessThanOrEqual($image->height());
})->with(ProfileImage::cases());

test('css aspect ratio mirrors the cropper aspect ratio', function (ProfileImage $image): void {
    expect($image->cssAspectRatio())->toBe(str_replace(':', ' / ', $image->aspectRatio()));
})->with(ProfileImage::cases());

test('cover is wide and avatar is square', function (): void {
    expect(ProfileImage::Cover->aspectRatio())->toBe('3:1')
        ->and(ProfileImage::Avatar->aspectRatio())->toBe('1:1');
});
