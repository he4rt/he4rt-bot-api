<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Concerns;

use He4rt\Identity\User\Enums\ProfileImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Avatar e capa do perfil: como são guardados, convertidos e enquadrados.
 *
 * As dimensões e limites moram no enum ProfileImage; aqui fica só o que
 * acontece com o arquivo.
 */
trait HasProfileImages
{
    /**
     * Chamado pelo `registerMediaCollections()` do model: o media library
     * também define esse método, e dois traits com o mesmo nome colidem.
     */
    public function registerProfileImageCollections(): void
    {
        foreach (ProfileImage::cases() as $image) {
            $this->addMediaCollection($image->value)
                ->singleFile()
                ->useDisk('public')
                ->acceptsMimeTypes(ProfileImage::mimeTypes())
                ->registerMediaConversions(function (?Media $media = null) use ($image): void {
                    // O que é servido inteiro não passa por aqui: converter
                    // acharia o primeiro quadro de um GIF e mataria a animação.
                    // O enquadramento desses fica por conta do object-cover.
                    if (in_array($media?->mime_type, ProfileImage::unconvertedMimeTypes(), strict: true)) {
                        return;
                    }

                    // Para o resto, o front recebe sempre a mesma dimensão e o
                    // mesmo formato, independente do arquivo enviado. Aqui
                    // também é onde o upscale acontece, quando necessário.
                    $this->addMediaConversion($image->value)
                        ->nonQueued()
                        ->fit(Fit::Crop, $image->width(), $image->height())
                        ->format('webp');
                });
        }
    }

    public function putImage(ProfileImage $image, UploadedFile $file): void
    {
        $this->refreshMediaRelation();

        // A collection é singleFile: o media library troca a imagem anterior.
        $this->addMedia($file)
            ->usingFileName(Str::uuid()->toString().'.'.$this->extensionOf($file))
            ->toMediaCollection($image->value);
    }

    public function removeImage(ProfileImage $image): void
    {
        $this->refreshMediaRelation();

        $this->clearMediaCollection($image->value);
    }

    /**
     * URL da conversão normalizada, caindo no original quando não existe
     * conversão: mídia antiga, ou formato que é servido como chega.
     */
    public function imageUrl(ProfileImage $image): ?string
    {
        $media = $this->getFirstMedia($image->value);

        if (!$media instanceof Media) {
            return null;
        }

        return $media->hasGeneratedConversion($image->value)
            ? $media->getUrl($image->value)
            : $media->getUrl();
    }

    /**
     * Imagem servida inteira, sem conversão, depende do enquadramento para
     * decidir o que aparece no recorte da tela.
     */
    public function imageNeedsFraming(ProfileImage $image): bool
    {
        $media = $this->getFirstMedia($image->value);

        return $media instanceof Media && !$media->hasGeneratedConversion($image->value);
    }

    /**
     * Faixa vertical da imagem que aparece no recorte da tela, em porcentagem.
     */
    public function imageFocalY(ProfileImage $image): int
    {
        $focalY = $this->getFirstMedia($image->value)?->getCustomProperty('focal_y');

        return is_numeric($focalY) ? (int) $focalY : ProfileImage::DEFAULT_FOCAL_Y;
    }

    public function setImageFocalY(ProfileImage $image, int $focalY): void
    {
        $media = $this->getFirstMedia($image->value);

        if (!$media instanceof Media) {
            return;
        }

        $media->setCustomProperty('focal_y', max(0, min(100, $focalY)));
        $media->save();
    }

    /**
     * O media library resolve `singleFile` lendo a relação já carregada. Numa
     * requisição Livewire ela foi carregada na renderização, antes do upload,
     * então sem descartar o cache a imagem anterior não é encontrada e fica
     * órfã no disco.
     */
    private function refreshMediaRelation(): void
    {
        $this->unsetRelation('media');
    }

    private function extensionOf(UploadedFile $file): string
    {
        return $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
    }
}
