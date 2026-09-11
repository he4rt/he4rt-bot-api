<?php

declare(strict_types=1);

use Pest\Rector\Rules\ChainExpectCallsRector;
use Pest\Rector\Rules\Pest2ToPest3\UsesToExtendRector;
use Pest\Rector\Set\PestSetList;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\Config\RectorConfig;
use Rector\Transform\Rector\ArrayDimFetch\ArrayDimFetchToMethodCallRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use RectorLaravel\Rector\Class_\AddHasFactoryToModelsRector;
use RectorLaravel\Rector\Class_\AliasesPropertyToAliasesAttributeRector;
use RectorLaravel\Rector\Class_\AppendsPropertyToAppendsAttributeRector;
use RectorLaravel\Rector\Class_\CollectedByPropertyToCollectedByAttributeRector;
use RectorLaravel\Rector\Class_\CollectsPropertyToCollectsAttributeRector;
use RectorLaravel\Rector\Class_\CommandHiddenPropertyToHiddenAttributeRector;
use RectorLaravel\Rector\Class_\DateFormatPropertyToDateFormatAttributeRector;
use RectorLaravel\Rector\Class_\DescriptionPropertyToDescriptionAttributeRector;
use RectorLaravel\Rector\Class_\EmptyGuardedPropertyToUnguardedAttributeRector;
use RectorLaravel\Rector\Class_\ErrorBagPropertyToErrorBagAttributeRector;
use RectorLaravel\Rector\Class_\FillablePropertyToFillableAttributeRector;
use RectorLaravel\Rector\Class_\GuardedPropertyToGuardedAttributeRector;
use RectorLaravel\Rector\Class_\HelpPropertyToHelpAttributeRector;
use RectorLaravel\Rector\Class_\HiddenPropertyToHiddenAttributeRector;
use RectorLaravel\Rector\Class_\PreserveKeysPropertyToPreserveKeysAttributeRector;
use RectorLaravel\Rector\Class_\RouteKeyMethodToRouteKeyAttributeRector;
use RectorLaravel\Rector\Class_\SignaturePropertyToSignatureAttributeRector;
use RectorLaravel\Rector\Class_\TouchesPropertyToTouchesAttributeRector;
use RectorLaravel\Rector\Class_\VisiblePropertyToVisibleAttributeRector;
use RectorLaravel\Rector\Coalesce\ApplyDefaultInsteadOfNullCoalesceRector;
use RectorLaravel\Rector\Empty_\EmptyToBlankAndFilledFuncRector;
use RectorLaravel\Rector\FuncCall\ConfigToTypedConfigMethodCallRector;
use RectorLaravel\Set\LaravelSetList;

$laravel13Attributes = [
    // Console
    AliasesPropertyToAliasesAttributeRector::class,
    CommandHiddenPropertyToHiddenAttributeRector::class,
    DescriptionPropertyToDescriptionAttributeRector::class,
    HelpPropertyToHelpAttributeRector::class,
    SignaturePropertyToSignatureAttributeRector::class,
    // Eloquent
    AppendsPropertyToAppendsAttributeRector::class,
    CollectedByPropertyToCollectedByAttributeRector::class,
    DateFormatPropertyToDateFormatAttributeRector::class,
    EmptyGuardedPropertyToUnguardedAttributeRector::class,
    FillablePropertyToFillableAttributeRector::class,
    GuardedPropertyToGuardedAttributeRector::class,
    HiddenPropertyToHiddenAttributeRector::class,
    RouteKeyMethodToRouteKeyAttributeRector::class,
    TouchesPropertyToTouchesAttributeRector::class,
    VisiblePropertyToVisibleAttributeRector::class,
    // API Resource
    CollectsPropertyToCollectsAttributeRector::class,
    PreserveKeysPropertyToPreserveKeysAttributeRector::class,
    // Form Request
    ErrorBagPropertyToErrorBagAttributeRector::class,
];

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/lang',
        __DIR__.'/tests',
        __DIR__.'/app-modules/*/src',
        __DIR__.'/app-modules/*/config',
        __DIR__.'/app-modules/*/database',
        __DIR__.'/app-modules/*/routes',
        __DIR__.'/app-modules/*/lang',
        __DIR__.'/app-modules/*/tests',
    ])
    ->withSkip([
        AddArrowFunctionReturnTypeRector::class,
        AddHasFactoryToModelsRector::class,
        PostIncDecToPreIncDecRector::class,
        ArrayDimFetchToMethodCallRector::class,
        UsesToExtendRector::class,
        ...$laravel13Attributes,
        __DIR__.'/bootstrap/cache',
    ])
    ->withCache(cacheDirectory: __DIR__.'/.cache/rector', cacheClass: FileCacheStorage::class)
    ->withImportNames()
    ->withRootFiles()
    ->withPhpSets(php84: true)
    ->withComposerBased(laravel: true)
    ->withBootstrapFiles([__DIR__.'/vendor/larastan/larastan/bootstrap.php'])
    ->withPHPStanConfigs([__DIR__.'/phpstan.neon'])
    ->reportUnusedSkips()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        namedArgs: true,
        instanceOf: true,
        earlyReturn: true,
        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
    )
    ->withRules([
        ApplyDefaultInsteadOfNullCoalesceRector::class,
        EmptyToBlankAndFilledFuncRector::class,
        ConfigToTypedConfigMethodCallRector::class,
    ])
    ->withSets([
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_TESTING,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
        PestSetList::CODING_STYLE,
    ])
    ->withConfiguredRule(ChainExpectCallsRector::class, [
        ChainExpectCallsRector::MERGE_DIFFERENT_VARIABLES => false,
    ]);
