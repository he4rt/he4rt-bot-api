<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Teto de upload que a aplicação pode prometer sem mentir.
 *
 * O PHP recusa o arquivo antes de qualquer validação do Laravel, e o que sobra
 * na tela é um "failed to upload" sem explicação. Anunciar o menor entre o
 * limite desejado e o que a infra aceita faz o erro acontecer no lugar certo,
 * com a mensagem certa.
 */
final class UploadLimit
{
    public static function kilobytes(int $desired): int
    {
        $limit = $desired;

        foreach (['upload_max_filesize', 'post_max_size'] as $directive) {
            $ceiling = self::fromIni($directive);

            if ($ceiling !== null) {
                $limit = min($limit, $ceiling);
            }
        }

        return $limit;
    }

    private static function fromIni(string $directive): ?int
    {
        $value = ini_get($directive);

        if (!is_string($value) || mb_trim($value) === '') {
            return null;
        }

        $kilobytes = self::toKilobytes(mb_trim($value));

        // Zero, na configuração do PHP, quer dizer sem limite.
        return $kilobytes > 0 ? $kilobytes : null;
    }

    private static function toKilobytes(string $value): int
    {
        $number = (int) $value;

        return match (mb_strtolower(mb_substr($value, -1))) {
            'g' => $number * 1_024 * 1_024,
            'm' => $number * 1_024,
            'k' => $number,
            default => (int) ceil($number / 1_024),
        };
    }
}
