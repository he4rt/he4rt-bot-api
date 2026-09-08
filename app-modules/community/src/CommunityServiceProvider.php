<?php

declare(strict_types=1);

namespace He4rt\Community;

use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\Actions\ComposePromotions;
use He4rt\Community\Retrospective\Actions\ResolvePeople;
use He4rt\Community\Retrospective\Contracts\PersonDirectory;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CommunityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Resolve as fontes descobertas por tag no boundary do container; o
        // domínio depende só do contrato (dele), nunca das implementações
        // concretas em integration-github/activity.
        $this->app->bind(CompileSnapshot::class, static function (Application $app): CompileSnapshot {
            /** @var iterable<RetrospectiveSource> $sources */
            $sources = $app->tagged('retrospective.source');

            return new CompileSnapshot($sources, $app->make(ComposePromotions::class));
        });

        $this->app->bind(PersonDirectory::class, ResolvePeople::class);

        // Mesma descoberta por tag: a orquestração pergunta a cada fonte o que ela
        // sabe sobre uma pessoa, e ignora quem não implementa MeasuresPerson.
        $this->app->bind(ComposePromotions::class, static function (Application $app): ComposePromotions {
            /** @var iterable<RetrospectiveSource> $sources */
            $sources = $app->tagged('retrospective.source');

            return new ComposePromotions($sources, $app->make(PersonDirectory::class));
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
