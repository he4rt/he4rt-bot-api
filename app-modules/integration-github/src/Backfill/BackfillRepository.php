<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Backfill;

use He4rt\IntegrationGithub\Contributions\DTOs\NewContributionDTO;
use He4rt\IntegrationGithub\Contributions\RecordContribution;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\GetPullRequest;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListCommits;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssueComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssues;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviewComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviews;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequests;
use Illuminate\Support\Str;
use Saloon\Http\Request;

final readonly class BackfillRepository
{
    private const int PER_PAGE = 100;

    public function __construct(
        private GitHubApiConnector $github,
        private RecordContribution $recorder,
    ) {}

    /**
     * Por padrão é incremental: se o repositório já tem last_backfilled_at, só busca o
     * que mudou desde (last_backfilled_at − 1 dia) — a margem D-1 evita perder algo na
     * borda. Sem last_backfilled_at (ou com $full), varre o histórico inteiro.
     *
     * @param  (callable(NewContributionDTO, bool): void)|null  $onProgress  Chamado a cada contribuição gravada (feedback de progresso).
     */
    public function execute(GithubRepository $repository, ?callable $onProgress = null, bool $full = false): void
    {
        $repo = $repository->full_name;

        $since = $full || $repository->last_backfilled_at === null
            ? null
            : $repository->last_backfilled_at->subDay()->utc()->format('Y-m-d\TH:i:s\Z');

        $this->backfillPullRequests($repo, $since, $onProgress);
        $this->backfillIssues($repo, $since, $onProgress);
        $this->backfillIssueComments($repo, $since, $onProgress);
        $this->backfillReviewComments($repo, $since, $onProgress);
        $this->backfillCommits($repo, $since, $onProgress);
    }

    /**
     * @param  (callable(NewContributionDTO, bool): void)|null  $onProgress
     */
    private function backfillPullRequests(string $repo, ?string $since, ?callable $onProgress): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequests($repo, $page, self::PER_PAGE),
            function (array $pr) use ($repo, $onProgress): void {
                $number = $this->intFrom($pr, 'number') ?? 0;
                /** @var array<string, mixed> $prSize */
                $prSize = (array) $this->github->send(new GetPullRequest($repo, $number))->throw()->json();
                $login = $this->actorLogin($pr);

                $this->record(new NewContributionDTO(
                    repo: $repo,
                    type: ContributionType::Pr,
                    externalRef: ContributionType::Pr->ref($number),
                    actorLogin: $login,
                    actorId: $this->actorId($pr),
                    occurredAt: $this->stringFrom($pr, 'created_at'),
                    targetRef: null,
                    metadata: [
                        'title' => data_get($pr, 'title'),
                        'state' => data_get($pr, 'state'),
                        'merged' => data_get($pr, 'merged_at') !== null,
                        'merged_at' => data_get($pr, 'merged_at'),
                        'url' => data_get($pr, 'html_url'),
                        'additions' => $this->intFrom($prSize, 'additions') ?? 0,
                        'deletions' => $this->intFrom($prSize, 'deletions') ?? 0,
                        'changed_files' => $this->intFrom($prSize, 'changed_files') ?? 0,
                        'is_bot' => $this->isBot($login),
                    ],
                ), $onProgress);

                $this->backfillReviews($repo, $number, $onProgress);
            },
            // PRs vêm ordenados por updated desc: ao cruzar o corte, todo o resto é mais
            // antigo — paramos de paginar (mata o N+1 de GetPullRequest + reviews).
            reachedEnd: $since === null
                ? null
                : function (array $pr) use ($since): bool {
                    /** @var array<string, mixed> $pr */
                    $updatedAt = $this->stringFrom($pr, 'updated_at');

                    return $updatedAt !== '' && $updatedAt < $since;
                },
        );
    }

    /**
     * @param  (callable(NewContributionDTO, bool): void)|null  $onProgress
     */
    private function backfillReviews(string $repo, int $number, ?callable $onProgress): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequestReviews($repo, $number, $page, self::PER_PAGE),
            function (array $review) use ($repo, $number, $onProgress): void {
                $submittedAt = $this->stringFrom($review, 'submitted_at');

                // Reviews PENDING (rascunho não enviado) não têm submitted_at — a API
                // lista o rascunho do próprio usuário do token. Não é contribuição.
                if ($submittedAt === '') {
                    return;
                }

                $login = $this->actorLogin($review);

                $this->record(new NewContributionDTO(
                    repo: $repo,
                    type: ContributionType::Review,
                    externalRef: ContributionType::Review->ref($this->stringFrom($review, 'id')),
                    actorLogin: $login,
                    actorId: $this->actorId($review),
                    occurredAt: $submittedAt,
                    targetRef: ContributionType::Pr->ref($number),
                    metadata: [
                        'state' => data_get($review, 'state'),
                        'is_bot' => $this->isBot($login),
                    ],
                ), $onProgress);
            },
        );
    }

    /**
     * @param  (callable(NewContributionDTO, bool): void)|null  $onProgress
     */
    private function backfillIssues(string $repo, ?string $since, ?callable $onProgress): void
    {
        $this->paginate(
            fn (int $page): Request => new ListIssues($repo, $page, self::PER_PAGE, $since),
            function (array $issue) use ($repo, $onProgress): void {
                if (isset($issue['pull_request'])) {
                    return; // o endpoint de issues também devolve PRs; estes já entram via backfillPullRequests
                }

                $login = $this->actorLogin($issue);

                $this->record(new NewContributionDTO(
                    repo: $repo,
                    type: ContributionType::Issue,
                    externalRef: ContributionType::Issue->ref($this->stringFrom($issue, 'number')),
                    actorLogin: $login,
                    actorId: $this->actorId($issue),
                    occurredAt: $this->stringFrom($issue, 'created_at'),
                    targetRef: null,
                    metadata: [
                        'title' => data_get($issue, 'title'),
                        'state' => data_get($issue, 'state'),
                        'url' => data_get($issue, 'html_url'),
                        'is_bot' => $this->isBot($login),
                    ],
                ), $onProgress);
            },
        );
    }

    /**
     * @param  (callable(NewContributionDTO, bool): void)|null  $onProgress
     */
    private function backfillIssueComments(string $repo, ?string $since, ?callable $onProgress): void
    {
        $this->paginate(
            fn (int $page): Request => new ListIssueComments($repo, $page, self::PER_PAGE, $since),
            function (array $comment) use ($repo, $onProgress): void {
                $login = $this->actorLogin($comment);

                // A REST list de issue-comments não diz se o item é de PR ou issue; o
                // html_url revela (.../pull/N vs .../issues/N). Espelha a lógica do webhook
                // (issue.pull_request) pra que comentário de PR aponte para pr:N — sem isso
                // o corte pós-merge não enxerga esses comentários.
                $htmlUrl = $this->stringFrom($comment, 'html_url');
                $isPr = str_contains($htmlUrl, '/pull/');
                $kind = $isPr ? ContributionType::Pr : ContributionType::Issue;

                $this->record(new NewContributionDTO(
                    repo: $repo,
                    type: ContributionType::Comment,
                    externalRef: ContributionType::Comment->ref($this->stringFrom($comment, 'id')),
                    actorLogin: $login,
                    actorId: $this->actorId($comment),
                    occurredAt: $this->stringFrom($comment, 'created_at'),
                    targetRef: $this->targetRefFromUrl($htmlUrl, $kind),
                    metadata: [
                        'url' => $htmlUrl,
                        'kind' => $isPr ? 'pr' : 'issue',
                        'is_bot' => $this->isBot($login),
                    ],
                ), $onProgress);
            },
        );
    }

    /**
     * @param  (callable(NewContributionDTO, bool): void)|null  $onProgress
     */
    private function backfillReviewComments(string $repo, ?string $since, ?callable $onProgress): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequestReviewComments($repo, $page, self::PER_PAGE, $since),
            function (array $comment) use ($repo, $onProgress): void {
                $login = $this->actorLogin($comment);

                $this->record(new NewContributionDTO(
                    repo: $repo,
                    type: ContributionType::ReviewComment,
                    externalRef: ContributionType::ReviewComment->ref($this->stringFrom($comment, 'id')),
                    actorLogin: $login,
                    actorId: $this->actorId($comment),
                    occurredAt: $this->stringFrom($comment, 'created_at'),
                    targetRef: $this->targetRefFromUrl($this->stringFrom($comment, 'pull_request_url'), ContributionType::Pr),
                    metadata: [
                        'url' => data_get($comment, 'html_url'),
                        'kind' => 'pr',
                        'is_bot' => $this->isBot($login),
                    ],
                ), $onProgress);
            },
        );
    }

    /**
     * @param  (callable(NewContributionDTO, bool): void)|null  $onProgress
     */
    private function backfillCommits(string $repo, ?string $since, ?callable $onProgress): void
    {
        $this->paginate(
            fn (int $page): Request => new ListCommits($repo, $page, self::PER_PAGE, $since),
            function (array $commit) use ($repo, $onProgress): void {
                $login = $this->stringFrom($commit, 'author.login')
                    ?: $this->stringFrom($commit, 'commit.author.name', 'ghost');

                $this->record(new NewContributionDTO(
                    repo: $repo,
                    type: ContributionType::Commit,
                    externalRef: ContributionType::Commit->ref($this->stringFrom($commit, 'sha')),
                    actorLogin: $login,
                    actorId: $this->intFrom($commit, 'author.id'),
                    occurredAt: $this->stringFrom($commit, 'commit.author.date'),
                    targetRef: null,
                    metadata: [
                        'url' => data_get($commit, 'html_url'),
                        'is_bot' => $this->isBot($login),
                    ],
                ), $onProgress);
            },
        );
    }

    /**
     * Único ponto de escrita: grava a contribuição e reporta ao callback de progresso,
     * sinalizando se a linha foi criada agora (true) ou apenas reatualizada (false) —
     * é esse flag que separa "novas" de "já existentes" no feedback do CLI.
     *
     * @param  (callable(NewContributionDTO, bool): void)|null  $onProgress
     */
    private function record(NewContributionDTO $contribution, ?callable $onProgress): void
    {
        $recorded = $this->recorder->execute($contribution, emit: true);

        if ($onProgress !== null) {
            $onProgress($contribution, $recorded->wasRecentlyCreated);
        }
    }

    /**
     * @param  callable(int): Request  $requestFor
     * @param  callable(array<string, mixed>): void  $onEach
     * @param  (callable(array<string, mixed>): bool)|null  $reachedEnd  Para a paginação ao retornar true (itens ordenados).
     */
    private function paginate(callable $requestFor, callable $onEach, ?callable $reachedEnd = null): void
    {
        $page = 1;

        do {
            /** @var list<array<string, mixed>> $items */
            $items = (array) $this->github->send($requestFor($page))->throw()->json();

            foreach ($items as $item) {
                if ($reachedEnd !== null && $reachedEnd($item)) {
                    return;
                }

                $onEach($item);
            }

            $page++;
        } while (count($items) === self::PER_PAGE);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringFrom(array $data, string $key, string $default = ''): string
    {
        $value = data_get($data, $key);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function intFrom(array $data, string $key): ?int
    {
        $value = data_get($data, $key);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function actorLogin(array $payload): string
    {
        return $this->stringFrom($payload, 'user.login', 'ghost');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function actorId(array $payload): ?int
    {
        return $this->intFrom($payload, 'user.id');
    }

    /**
     * Extrai o número do alvo de uma URL do GitHub — tanto de API
     * (.../pulls/304, .../issues/50) quanto de html_url (.../pull/304#issuecomment-1).
     */
    private function targetRefFromUrl(string $url, ContributionType $kind): ?string
    {
        $number = Str::match('~/(?:pulls?|issues)/(\d+)~', $url);

        return $number === '' ? null : $kind->ref($number);
    }

    private function isBot(string $login): bool
    {
        return Str::endsWith($login, '[bot]');
    }
}
