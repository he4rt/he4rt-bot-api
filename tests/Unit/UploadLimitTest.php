<?php

declare(strict_types=1);

use App\Support\UploadLimit;

test('never announces more than the application asked for', function (): void {
    expect(UploadLimit::kilobytes(4_096))->toBeLessThanOrEqual(4_096);
});

test('a request the environment can honour comes back untouched', function (): void {
    expect(UploadLimit::kilobytes(1))->toBe(1);
});

test('the php ceiling wins when it is the smaller one', function (): void {
    // Prometer mais do que o php.ini aceita gera um "failed to upload" sem
    // explicação, porque o PHP recusa o arquivo antes da validação.
    $directive = mb_trim((string) ini_get('upload_max_filesize'));

    if (!str_ends_with(mb_strtoupper($directive), 'M')) {
        $this->markTestSkipped('Este ambiente não declara o limite em megabytes.');
    }

    expect(UploadLimit::kilobytes(PHP_INT_MAX))->toBe(((int) $directive) * 1_024);
});
