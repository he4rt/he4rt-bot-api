<?php

declare(strict_types=1);

use He4rt\Marketing\ShortLink\Support\SlugGenerator;

test('slug is the apelido plus a five character base36 suffix', function (): void {
    expect(SlugGenerator::for('Discord'))->toMatch('/^discord-[0-9a-z]{5}$/');
});

test('apelido is normalized: accents, punctuation and spaces', function (): void {
    expect(SlugGenerator::for('Hacktoberfest 2026!'))->toStartWith('hacktoberfest-2026-')
        ->and(SlugGenerator::for('Programação Competitiva'))->toStartWith('programacao-competitiva-')
        ->and(SlugGenerator::for('  He4rt   Devs  '))->toStartWith('he4rt-devs-');
});

test('slug is always lowercase', function (): void {
    $slug = SlugGenerator::for('DISCORD Convite');

    expect($slug)->toBe(mb_strtolower($slug))
        ->and($slug)->toMatch('/^[a-z0-9-]+$/');
});

test('suffix never leaves the [0-9a-z] alphabet', function (): void {
    for ($i = 0; $i < 200; $i++) {
        expect(SlugGenerator::suffix())->toMatch('/^[0-9a-z]{5}$/');
    }
});

test('the same apelido yields different slugs', function (): void {
    $slugs = [];

    for ($i = 0; $i < 50; $i++) {
        $slugs[] = SlugGenerator::for('discord');
    }

    expect(array_unique($slugs))->toHaveCount(50);
});

test('base exposes the stable half of the slug on its own', function (): void {
    expect(SlugGenerator::base('Hacktoberfest 2026!'))->toBe('hacktoberfest-2026')
        ->and(SlugGenerator::for('Hacktoberfest 2026!'))
        ->toStartWith(SlugGenerator::base('Hacktoberfest 2026!').'-');
});
