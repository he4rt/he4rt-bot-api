<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->printer()->compact();

pest()->extend(TestCase::class)
    ->group('unit')
    ->in('Unit', '../app-modules/*/tests/Unit');

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->group('feature')
    ->in('Feature', '../app-modules/*/tests/Feature');

pest()->extend(TestCase::class)
    ->group('arch')
    ->in('Arch', '../app-modules/*/tests/Arch');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * O media library despacha as conversões para a conexão de fila do ambiente e
 * só depois do commit. Num teste, a fila é a real e a transação nunca comita,
 * então as conversões precisam rodar na hora para existirem.
 */
function runMediaConversionsInline(): void
{
    config()->set('media-library.queue_connection_name', 'sync');
    config()->set('media-library.queue_conversions_after_database_commit', value: false);
}
