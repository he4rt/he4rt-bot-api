<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\Articles;

use Generator;
use He4rt\Contents\Articles\Contracts\DiscoversByIdentity;
use He4rt\Contents\Articles\Contracts\DiscoversBySource;
use He4rt\Contents\Articles\Contracts\HydratesDetail;
use He4rt\Contents\Articles\DTOs\ArticleDTO;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationDevTo\Polling\DevToApiClient;
use Illuminate\Support\Facades\Log;

final readonly class DevToArticleProvider implements DiscoversByIdentity, DiscoversBySource, HydratesDetail
{
    public function __construct(
        private DevToApiClient $apiClient,
        private DevToArticleMapper $mapper,
    ) {}

    public function provider(): ContentProvider
    {
        return ContentProvider::DevTo;
    }

    /** @return iterable<ArticleDTO> */
    public function fetchFromSource(): iterable
    {
        $orgSlug = config('integration-devto.org_slug');
        $page = 1;

        do {
            $articles = $this->apiClient->getArticlesByOrg($orgSlug, $page);

            foreach ($articles as $article) {
                $dto = $this->mapper->fromListing($article);

                if (!$dto instanceof ArticleDTO) {
                    Log::warning('DevTo article provider: skipping malformed listing payload', [
                        'payload' => $article,
                    ]);

                    continue;
                }

                yield $dto;
            }

            $page++;
        } while (count($articles) === 30);
    }

    /** @return iterable<ArticleDTO> */
    public function fetchForIdentity(ExternalIdentity $identity): iterable
    {
        $apiKey = $identity->credentials->getApiKey();

        if ($apiKey === null) {
            Log::info('DevTo article provider: identity has no api key, skipping', [
                'external_identity_id' => $identity->id,
            ]);

            return;
        }

        yield from $this->fetchForApiKey($apiKey);
    }

    public function fetchDetail(ArticleDTO $shallow): ArticleDTO
    {
        $payload = $this->apiClient->getArticle((int) $shallow->externalId);

        return $this->mapper->fromDetail($payload, $shallow);
    }

    /** @return Generator<ArticleDTO> */
    private function fetchForApiKey(string $apiKey): Generator
    {
        $page = 1;

        do {
            $articles = $this->apiClient->getMyPublishedArticles($apiKey, $page);

            foreach ($articles as $article) {
                $dto = $this->mapper->fromListing($article);

                if (!$dto instanceof ArticleDTO) {
                    Log::warning('DevTo article provider: skipping malformed listing payload', [
                        'payload' => $article,
                    ]);

                    continue;
                }

                yield $dto;
            }

            $page++;
        } while (count($articles) === 30);
    }
}
