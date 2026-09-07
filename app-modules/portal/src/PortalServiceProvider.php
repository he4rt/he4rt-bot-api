<?php

declare(strict_types=1);

namespace He4rt\Portal;

use He4rt\Portal\Articles\ArticlesPage;
use He4rt\Portal\Home\HeroSection;
use He4rt\Portal\Home\Homepage;
use He4rt\Portal\Live\LiveChat;
use He4rt\Portal\Live\LivePage;
use He4rt\Portal\Retrospective\CommunityRetrospectivePage;
use He4rt\Portal\ShortLink\ShortLinkRedirectController;
use He4rt\Portal\Sitemap\SitemapController;
use He4rt\Portal\SocialLinks\SocialLinksPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Head\HeadServiceProvider;
use Livewire\Livewire;

class PortalServiceProvider extends ServiceProvider
{
    /**
     * O laravel/head registra a macro Route::withHead() no boot dele, e o
     * autodiscovery ordena `he4rt/portal` antes de `laravel/head`. Registrar o
     * provider aqui o coloca na fila de boot à frente deste módulo, garantindo
     * que a macro exista quando as rotas abaixo forem declaradas.
     */
    public function register(): void
    {
        $this->app->register(HeadServiceProvider::class);
    }

    public function boot(): void
    {
        /*
         * O grupo `web` é obrigatório e explícito: rotas registradas por um módulo não
         * herdam middleware nenhum (o modular carrega os arquivos de rota sem grupo —
         * ver identity/routes/authentication-routes.php). Sem `web` não há
         * StartSession, então Livewire fica sem sessão nem CSRF e o guard do preview
         * (auth()->check() no mount) reprova TODO mundo com 403.
         */
        Route::middleware('web')->group(static function (): void {
            /*
             * O metadata de <head> mora na rota, não no componente: são páginas
             * semi-estáticas cujo título/description são conhecidos de antemão.
             * Os defaults (App\Support\Seo\SiteHead) preenchem o resto.
             */
            Route::get('/', Homepage::class)
                ->name('home')
                ->withHead(
                    // `exact` evita o sufixo " - He4rt Developers" duplicar a marca na home.
                    title: ['value' => 'He4rt Developers — Comunidade de desenvolvedores', 'exact' => true],
                );

            Route::get('/redes', SocialLinksPage::class)
                ->name('social-links')
                ->withHead(
                    title: 'Nossas redes',
                    description: 'Todos os canais oficiais da He4rt Developers: Discord, GitHub, LinkedIn, Instagram, X e WhatsApp.',
                );

            Route::get('/artigos', ArticlesPage::class)
                ->name('articles')
                ->withHead(
                    title: 'Artigos da comunidade',
                    description: 'Os artigos publicados pela organização He4rt Developers no dev.to, por tema e por quem escreveu.',
                );

            Route::get('/live', LivePage::class)
                ->name('portal.live')
                ->withHead(
                    title: 'Live da comunidade',
                    description: 'Acompanhe ao vivo as transmissões da comunidade He4rt Developers.',
                );

            Route::get('/comunidade/retrospectiva', CommunityRetrospectivePage::class)
                ->name('community.retrospective')
                ->withHead(
                    title: 'Quem fez a He4rt bater',
                    description: 'Retrospectiva das contribuições open source da comunidade He4rt Developers: pull requests, issues e reviews por pessoa e por repositório.',
                );

            /*
             * Preview do operador: monta uma edição específica (rascunho ao vivo ou
             * publicada) pelo mesmo render path da pública. O componente aborta com 403
             * para visitantes (não há rota de login web; o guard fica no mount).
             *
             * Rascunho não indexa, e o canonical aponta para a edição pública.
             */
            Route::get('/comunidade/retrospectiva/{retrospective}/preview', CommunityRetrospectivePage::class)
                ->name('community.retrospective.preview')
                ->withHead(
                    title: 'Preview da retrospectiva',
                    robots: ['noindex', 'nofollow'],
                    canonical: '/comunidade/retrospectiva',
                );

            /*
             * The public edge of the shortener (app-modules/marketing). Slugs are
             * canonical in lowercase, so the constraint sends `/l/Discord-A3F9K` to
             * the framework 404 without a lookup.
             *
             * The head metadata is for the sad path, the only one that renders a
             * page. Without it, each dead slug would be `index, follow` under the
             * portal defaults.
             */
            Route::get('/l/{slug}', ShortLinkRedirectController::class)
                ->where('slug', '[a-z0-9-]+')
                ->name('short-link.redirect')
                ->withHead(
                    title: 'Link indisponível',
                    description: 'O link curto que você abriu não está mais disponível.',
                    robots: ['noindex', 'follow'],
                    // The default canonical is the current URL, which would write the
                    // slug into the head and make each dead page different.
                    canonical: '/',
                );

            Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
        });

        Livewire::component('hero-section', HeroSection::class);
        Livewire::component('portal.live-chat', LiveChat::class);
    }
}
