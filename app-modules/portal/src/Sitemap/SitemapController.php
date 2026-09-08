<?php

declare(strict_types=1);

namespace He4rt\Portal\Sitemap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Sitemap das páginas públicas do portal.
 *
 * Cobre apenas as rotas servidas por este módulo. Painéis (/admin, /app) e
 * rotas de autenticação ficam de fora de propósito — são bloqueados no
 * robots.txt e marcados com `noindex` pelo render hook do Filament.
 */
final class SitemapController extends Controller
{
    /**
     * Rotas nomeadas do portal e sua prioridade relativa no sitemap.
     *
     * @var array<string, array{changefreq: string, priority: string}>
     */
    private const array PAGES = [
        'home' => ['changefreq' => 'daily', 'priority' => '1.0'],
        'social-links' => ['changefreq' => 'monthly', 'priority' => '0.6'],
        'community.retrospective' => ['changefreq' => 'weekly', 'priority' => '0.7'],
        'gallery' => ['changefreq' => 'weekly', 'priority' => '0.7'],
    ];

    public function __invoke(): Response
    {
        $urls = [];

        foreach (self::PAGES as $routeName => $attributes) {
            $urls[] = [
                'loc' => route($routeName),
                ...$attributes,
            ];
        }

        return response()
            ->view('portal::sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
