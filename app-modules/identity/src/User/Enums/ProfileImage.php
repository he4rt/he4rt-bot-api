<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Enums;

/**
 * Fonte única de configuração das imagens de perfil: nome da collection no
 * media library, dimensão servida ao front, piso aceito no upload, proporção
 * e limite de tamanho.
 *
 * Variação de imagem aqui é dado, não subclasse: uma imagem nova entra como
 * mais um case e já nasce com upload, recorte, validação e conversão.
 *
 * Altura é sempre derivada da proporção, nunca armazenada, senão largura e
 * altura divergem e o recorte deixa de ser previsível.
 */
enum ProfileImage: string
{
    case Avatar = 'avatar';
    case Cover = 'cover';

    /** Centro da imagem: o recorte pega a faixa do meio. */
    public const int DEFAULT_FOCAL_Y = 50;

    /**
     * @return list<string>
     */
    public static function mimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }

    /**
     * Formatos servidos exatamente como chegam, sem conversão.
     *
     * @return list<string>
     */
    public static function unconvertedMimeTypes(): array
    {
        return ['image/gif'];
    }

    /**
     * Teto menor para o que é servido sem conversão: nesses formatos o peso do
     * upload é o peso que cada visita ao perfil baixa.
     */
    public static function unconvertedMaxKilobytes(): int
    {
        return 2_048;
    }

    /**
     * Os mesmos formatos, como o usuário os reconhece.
     *
     * GIF entra e é servido como chega, com a animação intacta: converter
     * leria só o primeiro quadro no GD. Quem recorta pelo editor perde a
     * animação, porque o recorte acontece no canvas do browser.
     */
    public static function formatLabels(): string
    {
        return 'JPG, PNG, GIF, WEBP';
    }

    /**
     * Largura da conversão servida ao front.
     */
    public function width(): int
    {
        return match ($this) {
            self::Avatar => 500,
            self::Cover => 1_800,
        };
    }

    public function height(): int
    {
        return $this->heightFor($this->width());
    }

    /**
     * Menor largura aceita no upload. Abaixo disso o upscale ficaria agressivo
     * demais; entre o piso e a largura ideal, quem estica é a conversão.
     */
    public function minWidth(): int
    {
        return match ($this) {
            self::Avatar => 256,
            self::Cover => 1_200,
        };
    }

    public function minHeight(): int
    {
        return $this->heightFor($this->minWidth());
    }

    /**
     * Proporção no formato aceito pelo editor de recorte do Filament.
     */
    public function aspectRatio(): string
    {
        [$width, $height] = $this->ratio();

        return sprintf('%d:%d', $width, $height);
    }

    /**
     * A mesma proporção, no formato da propriedade CSS `aspect-ratio`. O blade
     * não deve conhecer esses números por conta própria.
     */
    public function cssAspectRatio(): string
    {
        [$width, $height] = $this->ratio();

        return sprintf('%d / %d', $width, $height);
    }

    public function maxKilobytes(): int
    {
        return match ($this) {
            self::Avatar => 2_048,
            self::Cover => 4_096,
        };
    }

    /**
     * @return array{int, int}
     */
    private function ratio(): array
    {
        return match ($this) {
            self::Avatar => [1, 1],
            self::Cover => [3, 1],
        };
    }

    private function heightFor(int $width): int
    {
        [$ratioWidth, $ratioHeight] = $this->ratio();

        return intdiv($width * $ratioHeight, $ratioWidth);
    }
}
