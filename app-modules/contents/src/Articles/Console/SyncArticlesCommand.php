<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Console;

use He4rt\Contents\Articles\Actions\UpsertArticle;
use He4rt\Contents\Articles\ArticleProviderRegistry;
use He4rt\Contents\Articles\Console\Support\AuthorTally;
use He4rt\Contents\Articles\Console\Support\SyncFailure;
use He4rt\Contents\Articles\Console\Support\SyncTally;
use He4rt\Contents\Articles\Contracts\ArticleProvider;
use He4rt\Contents\Articles\Contracts\DiscoversByIdentity;
use He4rt\Contents\Articles\Contracts\DiscoversBySource;
use He4rt\Contents\Articles\Contracts\HydratesDetail;
use He4rt\Contents\Articles\DTOs\ArticleDTO;
use He4rt\Contents\Articles\Models\Article;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Prompts\Progress;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

#[Description(description: 'Sync articles from all registered content providers into the canonical catalogue')]
#[Signature(signature: 'contents:sync-articles {--provider=* : Limit the sync to these providers (e.g. devto)}')]
final class SyncArticlesCommand extends Command
{
    /** @var array<string, AuthorTally> */
    private array $authors = [];

    /** @var list<ExternalIdentity> */
    private array $identities = [];

    public function handle(ArticleProviderRegistry $registry, UpsertArticle $upsert): int
    {
        $providers = $this->selectedProviders($registry);

        if ($providers === []) {
            warning('Nenhum provider de artigos registrado para sincronizar.');

            return self::SUCCESS;
        }

        intro('contents:sync-articles');

        $tallies = [];

        foreach ($providers as $provider) {
            $tallies[] = $this->syncProvider($provider, $upsert);
        }

        $this->renderReport($tallies);

        return self::SUCCESS;
    }

    private function syncProvider(ArticleProvider $provider, UpsertArticle $upsert): SyncTally
    {
        $label = $provider->provider()->getLabel();
        $tally = new SyncTally($provider->provider());

        try {
            /** @var list<ArticleDTO> $articles */
            $articles = spin(
                callback: fn (): array => $this->discover($provider),
                message: "Descobrindo artigos em {$label}...",
            );
        } catch (Throwable $throwable) {
            $tally->fail(SyncFailure::fromThrowable($provider->provider(), 'descoberta', $throwable));
            $tally->finish();

            $this->logFailure($provider, $throwable, 'descoberta');

            return $tally;
        }

        $tally->discovered = count($articles);

        if ($articles === []) {
            $tally->finish();
            warning("{$label}: nenhum artigo encontrado.");

            return $tally;
        }

        progress(
            label: "Gravando artigos de {$label}",
            steps: $articles,
            callback: function (ArticleDTO $dto, Progress $progress) use ($provider, $upsert, $tally): void {
                $progress->hint(Str::limit($dto->title, 60));

                $this->persist($provider, $dto, $upsert, $tally);
            },
        );

        $tally->finish();

        return $tally;
    }

    private function persist(ArticleProvider $provider, ArticleDTO $dto, UpsertArticle $upsert, SyncTally $tally): void
    {
        try {
            $payload = $this->hydrateIfStale($provider, $dto);

            if ($payload !== $dto) {
                $tally->hydrated++;
            }

            $entry = $upsert->execute($provider->provider(), $payload);

            $tally->record($entry);
            $this->authorTally($entry->author_handle)->record($entry);
        } catch (Throwable $throwable) {
            $tally->fail(SyncFailure::fromThrowable($provider->provider(), $dto->externalId, $throwable));

            Log::error('contents: article sync failed', [
                'provider' => $provider->provider()->value,
                'external_id' => $dto->externalId,
                'exception' => $throwable,
            ]);
        }
    }

    /** @return list<ArticleDTO> */
    private function discover(ArticleProvider $provider): array
    {
        $articles = [];

        if ($provider instanceof DiscoversBySource) {
            foreach ($provider->fetchFromSource() as $dto) {
                $articles[] = $dto;
            }
        }

        if ($provider instanceof DiscoversByIdentity) {
            foreach ($this->connectedIdentitiesFor($provider) as $identity) {
                $this->identities[] = $identity;

                foreach ($provider->fetchForIdentity($identity) as $dto) {
                    $articles[] = $dto;
                }
            }
        }

        return $articles;
    }

    /**
     * @param  list<SyncTally>  $tallies
     */
    private function renderReport(array $tallies): void
    {
        table(
            headers: ['Provider', 'Encontrados', 'Novos', 'Atualizados', 'Detalhados', 'Falhas', 'Tempo'],
            rows: array_map(fn (SyncTally $tally): array => $tally->toRow(), $tallies),
        );

        $created = array_sum(array_map(fn (SyncTally $tally): int => $tally->created, $tallies));
        $updated = array_sum(array_map(fn (SyncTally $tally): int => $tally->updated, $tallies));
        $failed = array_sum(array_map(fn (SyncTally $tally): int => $tally->failed, $tallies));

        $failures = array_merge(...array_map(fn (SyncTally $tally): array => $tally->failures, $tallies));

        if ($failures !== []) {
            error(count($failures) === 1 ? '1 falha durante o sync:' : count($failures).' falhas durante o sync:');

            table(
                headers: ['Provider', 'Referência', 'Erro', 'Mensagem'],
                rows: array_map(fn (SyncFailure $failure): array => $failure->toRow(), $failures),
            );
        }

        $this->renderIdentities();
        $this->renderAuthors();

        $summary = "{$created} novos, {$updated} atualizados";

        outro($failed > 0 ? "{$summary}, {$failed} falhas — stack trace completo nos logs." : $summary.'.');
    }

    private function logFailure(ArticleProvider $provider, Throwable $exception, string $stage): void
    {
        Log::error('contents: provider sync failed', [
            'provider' => $provider->provider()->value,
            'stage' => $stage,
            'exception' => $exception,
        ]);
    }

    /** @return list<ArticleProvider> */
    private function selectedProviders(ArticleProviderRegistry $registry): array
    {
        /** @var list<string> $requested */
        $requested = (array) $this->option('provider');

        if ($requested === []) {
            return $registry->all();
        }

        $unknown = array_diff($requested, array_map(
            fn (ArticleProvider $provider): string => $provider->provider()->value,
            $registry->all(),
        ));

        if ($unknown !== []) {
            warning('Provider desconhecido: '.implode(', ', $unknown));
        }

        return array_values(array_filter(
            $registry->all(),
            fn (ArticleProvider $provider): bool => in_array($provider->provider()->value, $requested, strict: true),
        ));
    }

    private function authorTally(string $handle): AuthorTally
    {
        return $this->authors[$handle] ??= new AuthorTally($handle);
    }

    private function renderIdentities(): void
    {
        if ($this->identities === []) {
            return;
        }

        note('Identidades conectadas consultadas nesta rodada');

        table(
            headers: ['Usuário', 'Handle', 'Provider', 'Conectado em'],
            rows: array_map(fn (ExternalIdentity $identity): array => [
                $identity->model instanceof User ? $identity->model->name : '—',
                '@'.$this->identityHandle($identity),
                $identity->provider->value,
                $identity->connected_at?->format('d/m/Y') ?? '—',
            ], $this->identities),
        );
    }

    private function identityHandle(ExternalIdentity $identity): string
    {
        $username = $identity->metadata['username'] ?? null;

        return is_string($username) && $username !== '' ? $username : '?';
    }

    private function renderAuthors(): void
    {
        if ($this->authors === []) {
            return;
        }

        $authors = $this->authors;

        uasort($authors, fn (AuthorTally $a, AuthorTally $b): int => $b->articles <=> $a->articles);

        $unlinked = count(array_filter($authors, fn (AuthorTally $author): bool => !$author->isLinked()));

        note('Autores');

        table(
            headers: ['Autor', 'Usuário vinculado', 'Artigos', 'Novos', 'Atualizados'],
            rows: array_map(fn (AuthorTally $author): array => $author->toRow(), array_values($authors)),
        );

        if ($unlinked > 0) {
            warning($unlinked === 1
                ? '1 autor ainda não tem identidade conectada — o artigo fica órfão até a vinculação.'
                : "{$unlinked} autores ainda não têm identidade conectada — os artigos ficam órfãos até a vinculação.");
        }
    }

    private function hydrateIfStale(ArticleProvider $provider, ArticleDTO $dto): ArticleDTO
    {
        $entry = ContentEntry::query()
            ->with('contentable')
            ->where('provider', $provider->provider())
            ->where('external_id', $dto->externalId)
            ->first();

        $stored = $entry?->contentable instanceof Article ? $entry->contentable->source_edited_at : null;

        $isStale = $entry === null
            || $stored?->getTimestamp() !== $dto->sourceEditedAt?->getTimestamp();

        return $isStale && $provider instanceof HydratesDetail
            ? $provider->fetchDetail($dto)
            : $dto;
    }

    /** @return iterable<ExternalIdentity> */
    private function connectedIdentitiesFor(ArticleProvider $provider): iterable
    {
        $identityProvider = $provider->provider()->toIdentityProvider();

        if (!$identityProvider instanceof IdentityProvider) {
            return [];
        }

        return ExternalIdentity::query()
            ->with('model')
            ->whereMorphedTo('model', User::class)
            ->where('provider', $identityProvider)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->get();
    }
}
