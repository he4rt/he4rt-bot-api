<?php

declare(strict_types=1);

namespace He4rt\Events\Gallery\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Fonte única das versões de uma foto de álbum: nome da conversão no media
 * library, dimensão servida e como a imagem é enquadrada nela.
 *
 * Uma versão nova entra como mais um case e já nasce registrada na collection.
 */
enum PhotoConversion: string implements HasColor, HasDescription, HasLabel
{
    case Thumb = 'thumb';
    case Large = 'large';

    /**
     * GIF fica de fora de propósito: foto de evento não é animação, e aceitá-lo
     * exigiria o caminho "servido sem conversão" que o perfil precisou.
     *
     * @return list<string>
     */
    public static function mimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/webp'];
    }

    public static function maxKilobytes(): int
    {
        return 10_240;
    }

    public function width(): int
    {
        return match ($this) {
            self::Thumb => 640,
            self::Large => 1_920,
        };
    }

    public function height(): int
    {
        return match ($this) {
            self::Thumb => 480,
            self::Large => 1_920,
        };
    }

    /**
     * A miniatura é recortada para todos os cards terem a mesma proporção; a
     * ampliada só é limitada, preservando o enquadramento original.
     */
    public function fit(): Fit
    {
        return match ($this) {
            self::Thumb => Fit::Crop,
            self::Large => Fit::Max,
        };
    }

    /**
     * As conversões rodam em fila. Até a desta versão existir, quem exibe
     * recebe o arquivo original em vez de um link quebrado.
     */
    public function urlFor(Media $media): string
    {
        return $media->hasGeneratedConversion($this->value)
            ? $media->getUrl($this->value)
            : $media->getUrl();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Thumb => __('events::enums.photo_conversion.thumb'),
            self::Large => __('events::enums.photo_conversion.large'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Thumb => 'gray',
            self::Large => 'primary',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Thumb => __('events::enums.photo_conversion.thumb_description'),
            self::Large => __('events::enums.photo_conversion.large_description'),
        };
    }
}
