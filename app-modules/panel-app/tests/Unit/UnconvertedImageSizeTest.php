<?php

declare(strict_types=1);

use He4rt\PanelApp\Rules\UnconvertedImageSize;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * @param  int  $kilobytes  tamanho do arquivo enviado
 * @param  int  $ceiling  teto configurado para formatos sem conversão
 * @return array<int, string>
 */
function validateAgainstCeiling(string $name, string $mimeType, int $kilobytes, int $ceiling): array
{
    $validator = Validator::make(
        ['file' => UploadedFile::fake()->create($name, $kilobytes, $mimeType)],
        ['file' => [new UnconvertedImageSize($ceiling)]],
    );

    return $validator->errors()->get('file');
}

test('a gif above the ceiling is rejected', function (): void {
    $errors = validateAgainstCeiling('animado.gif', 'image/gif', 3_000, 2_048);

    expect($errors)->not->toBeEmpty()
        ->and($errors[0])->toContain('2');
});

test('a gif within the ceiling passes', function (): void {
    expect(validateAgainstCeiling('animado.gif', 'image/gif', 1_500, 2_048))->toBeEmpty();
});

test('a converted format is not subject to this ceiling', function (): void {
    // JPG vira webp de poucos KB na conversão: o peso do upload não é o peso
    // que a página serve, então o teto menor não se aplica a ele.
    expect(validateAgainstCeiling('capa.jpg', 'image/jpeg', 3_000, 2_048))->toBeEmpty();
});

test('the ceiling is honoured whatever number it is', function (): void {
    expect(validateAgainstCeiling('animado.gif', 'image/gif', 1_500, 1_024))->not->toBeEmpty()
        ->and(validateAgainstCeiling('animado.gif', 'image/gif', 1_500, 8_192))->toBeEmpty();
});
