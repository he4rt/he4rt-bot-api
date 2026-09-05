<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Rules;

use Closure;
use He4rt\Identity\User\Enums\ProfileImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Teto de tamanho para os formatos que são servidos sem conversão.
 *
 * Nesses formatos o peso do upload é o peso que cada visita ao perfil baixa,
 * então eles têm um limite menor que o dos demais. Quando o limite geral do
 * ambiente já é menor que este, quem reprova é o limite geral.
 */
final readonly class UnconvertedImageSize implements ValidationRule
{
    public function __construct(private int $maxKilobytes) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        if (!in_array($value->getMimeType(), ProfileImage::unconvertedMimeTypes(), strict: true)) {
            return;
        }

        if ($value->getSize() <= $this->maxKilobytes * 1_024) {
            return;
        }

        $fail('panel-app::profile.validation.image_unconverted_max_size')->translate([
            'gif_mb' => round($this->maxKilobytes / 1_024, 1),
            'formats' => ProfileImage::formatLabels(),
        ]);
    }
}
