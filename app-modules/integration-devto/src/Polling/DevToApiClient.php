<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\Polling;

use Illuminate\Support\Facades\Http;

final class DevToApiClient
{
    /** @return array<int, array<string, mixed>> */
    public function getArticlesByOrg(string $orgSlug, int $page = 1, int $perPage = 30): array
    {
        $baseUrl = config('integration-devto.api_base_url');

        $response = Http::get($baseUrl.'/articles', [
            'username' => $orgSlug,
            'per_page' => $perPage,
            'page' => $page,
        ]);

        return $response->json() ?? [];
    }

    /** @return array<string, mixed> */
    public function getArticle(int $articleId): array
    {
        $baseUrl = config('integration-devto.api_base_url');

        $response = Http::get(sprintf('%s/articles/%d', $baseUrl, $articleId));

        return $response->json() ?? [];
    }

    /** @return array<int, array<string, mixed>> */
    public function getMyPublishedArticles(string $apiKey, int $page = 1, int $perPage = 30): array
    {
        $baseUrl = config('integration-devto.api_base_url');

        $response = Http::withHeaders(['api-key' => $apiKey])
            ->get($baseUrl.'/articles/me/published', [
                'per_page' => $perPage,
                'page' => $page,
            ]);

        return $response->json() ?? [];
    }
}
