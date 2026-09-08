<?php

declare(strict_types=1);

use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;

test('appends the configured utm to a destination without query', function (): void {
    $utm = new UtmParameters(source: 'discord');

    expect($utm->appendTo('https://exemplo.com/pagina'))
        ->toBe('https://exemplo.com/pagina?utm_source=discord');
});

test('the destination own query wins over the configured utm', function (): void {
    $utm = new UtmParameters(source: 'discord');

    expect($utm->appendTo('https://exemplo.com/pagina?utm_source=newsletter'))
        ->toBe('https://exemplo.com/pagina?utm_source=newsletter');
});

test('the incoming query wins over the configured utm', function (): void {
    $utm = new UtmParameters(source: 'discord');

    expect($utm->appendTo('https://exemplo.com/pagina', ['utm_source' => 'twitter']))
        ->toBe('https://exemplo.com/pagina?utm_source=twitter');
});

test('the destination own query wins over the incoming query', function (): void {
    $utm = new UtmParameters();

    expect($utm->appendTo('https://exemplo.com/pagina?utm_source=newsletter', ['utm_source' => 'twitter']))
        ->toBe('https://exemplo.com/pagina?utm_source=newsletter');
});

test('the configured utm only fills what the incoming query left out', function (): void {
    $utm = new UtmParameters(source: 'discord', medium: 'post');

    expect($utm->appendTo('https://exemplo.com/pagina', ['utm_source' => 'twitter']))
        ->toBe('https://exemplo.com/pagina?utm_source=twitter&utm_medium=post');
});

test('the three precedence levels resolve in one call', function (): void {
    $utm = new UtmParameters(source: 'discord', medium: 'post', campaign: 'hacktoberfest');

    $url = $utm->appendTo(
        'https://exemplo.com/pagina?utm_campaign=cadastrada',
        ['utm_source' => 'twitter'],
    );

    expect($url)->toBe('https://exemplo.com/pagina?utm_campaign=cadastrada&utm_source=twitter&utm_medium=post');
});

test('a destination that already has a query gets an ampersand, not a second question mark', function (): void {
    $utm = new UtmParameters(source: 'discord');

    expect($utm->appendTo('https://exemplo.com/pagina?ref=abc'))
        ->toBe('https://exemplo.com/pagina?ref=abc&utm_source=discord');
});

test('the fragment stays at the end of the url', function (): void {
    $utm = new UtmParameters(source: 'discord');

    expect($utm->appendTo('https://exemplo.com/pagina#secao'))
        ->toBe('https://exemplo.com/pagina?utm_source=discord#secao')
        ->and($utm->appendTo('https://exemplo.com/pagina?ref=abc#secao'))
        ->toBe('https://exemplo.com/pagina?ref=abc&utm_source=discord#secao');
});

test('values are url encoded', function (): void {
    $utm = new UtmParameters(campaign: 'hacktober fest/2026 & cia');

    expect($utm->appendTo('https://exemplo.com/pagina'))
        ->toBe('https://exemplo.com/pagina?utm_campaign=hacktober%20fest%2F2026%20%26%20cia');
});

test('an empty utm with no incoming query returns the destination byte for byte', function (): void {
    $destination = 'https://exemplo.com/pagina?a=1&b=hello+world#secao';

    expect(new UtmParameters()->appendTo($destination))->toBe($destination);
});

test('non utm query params from the short url are carried to the destination', function (): void {
    expect(new UtmParameters()->appendTo('https://exemplo.com/pagina', ['ref' => 'dan']))
        ->toBe('https://exemplo.com/pagina?ref=dan');
});

test('empty incoming values are ignored', function (): void {
    $destination = 'https://exemplo.com/pagina';

    expect(new UtmParameters()->appendTo($destination, ['utm_source' => '', 'utm_medium' => '   ']))
        ->toBe($destination);
});

test('an unparseable destination is returned untouched', function (): void {
    $utm = new UtmParameters(source: 'discord');

    expect($utm->appendTo('http:///exemplo.com'))->toBe('http:///exemplo.com');
});

test('fromArray reads the canonical utm keys', function (): void {
    $utm = UtmParameters::fromArray([
        'utm_source' => 'discord',
        'utm_medium' => 'post',
        'utm_campaign' => 'hacktoberfest',
        'utm_term' => 'php',
        'utm_content' => 'banner',
    ]);

    expect($utm->source)->toBe('discord')
        ->and($utm->medium)->toBe('post')
        ->and($utm->campaign)->toBe('hacktoberfest')
        ->and($utm->term)->toBe('php')
        ->and($utm->content)->toBe('banner');
});

test('fromArray also reads the short form keys used by forms', function (): void {
    $utm = UtmParameters::fromArray(['source' => 'discord', 'medium' => 'post']);

    expect($utm->source)->toBe('discord')
        ->and($utm->medium)->toBe('post');
});

test('fromArray prefers the canonical key when both are present', function (): void {
    expect(UtmParameters::fromArray(['utm_source' => 'discord', 'source' => 'twitter'])->source)
        ->toBe('discord');
});

test('fromArray trims values and turns blanks and junk into null', function (): void {
    $utm = UtmParameters::fromArray([
        'utm_source' => '  discord  ',
        'utm_medium' => '   ',
        'utm_campaign' => ['nested'],
        'utm_term' => null,
    ]);

    expect($utm->source)->toBe('discord')
        ->and($utm->medium)->toBeNull()
        ->and($utm->campaign)->toBeNull()
        ->and($utm->term)->toBeNull();
});

test('toArray round trips through fromArray', function (): void {
    $utm = new UtmParameters(source: 'discord', medium: 'post', content: 'banner');

    expect(UtmParameters::fromArray($utm->toArray())->toArray())->toBe($utm->toArray());
});

test('toArray always exposes the five canonical keys', function (): void {
    expect(new UtmParameters(source: 'discord')->toArray())->toBe([
        'utm_source' => 'discord',
        'utm_medium' => null,
        'utm_campaign' => null,
        'utm_term' => null,
        'utm_content' => null,
    ]);
});

test('isEmpty only when every parameter is blank', function (): void {
    expect(new UtmParameters()->isEmpty())->toBeTrue()
        ->and(UtmParameters::fromArray([])->isEmpty())->toBeTrue()
        ->and(UtmParameters::fromArray(['utm_source' => '  '])->isEmpty())->toBeTrue()
        ->and(new UtmParameters(content: 'banner')->isEmpty())->toBeFalse();
});
