<?php

declare(strict_types=1);

namespace He4rt\Docs;

use Dedoc\Scramble\Scramble;
use He4rt\Docs\Console\Commands\CacheDocsCommand;
use He4rt\Docs\Discovery\Actions\BuildDocumentTreeAction;
use He4rt\Docs\Discovery\Actions\DiscoverDocumentSourcesAction;
use He4rt\Docs\Discovery\Contracts\DocumentTypeStrategyContract;
use He4rt\Docs\Discovery\DocumentRegistry;
use He4rt\Docs\Discovery\Strategies\AdrStrategy;
use He4rt\Docs\Discovery\Strategies\ContextMapStrategy;
use He4rt\Docs\Discovery\Strategies\ContextStrategy;
use He4rt\Docs\Discovery\Strategies\GuideStrategy;
use He4rt\Docs\Discovery\Strategies\IntroductionStrategy;
use He4rt\Docs\Discovery\Strategies\PlanStrategy;
use He4rt\Docs\Discovery\Strategies\PrdStrategy;
use He4rt\Docs\Discovery\Strategies\ReadmeStrategy;
use He4rt\Docs\Discovery\Strategies\SpecStrategy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class DocsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/docs.php', 'docs');

        // Order matters: the first strategy whose matches() is true owns the file.
        $this->app->tag([
            ContextMapStrategy::class,
            ContextStrategy::class,
            IntroductionStrategy::class,
            AdrStrategy::class,
            SpecStrategy::class,
            PlanStrategy::class,
            PrdStrategy::class,
            ReadmeStrategy::class,
            GuideStrategy::class,
        ], 'docs.strategies');

        $this->app->bind(static function (Application $app): BuildDocumentTreeAction {
            /** @var iterable<DocumentTypeStrategyContract> $strategies */
            $strategies = $app->tagged('docs.strategies');

            return new BuildDocumentTreeAction(
                $app->make(DiscoverDocumentSourcesAction::class),
                $strategies,
            );
        });

        $this->app->singleton(DocumentRegistry::class);
    }

    public function boot(): void
    {
        $this->commands([CacheDocsCommand::class]);

        Scramble::registerApi('3.x');

        Scramble::registerUiRoute(path: 'docs/3.x/api', api: '3.x');
        Scramble::registerJsonSpecificationRoute(path: 'docs/3.x/swagger.json', api: '3.x');
    }
}
