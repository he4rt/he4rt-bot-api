<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Backfill\Jobs;

use DateTimeInterface;
use He4rt\IntegrationGithub\Backfill\BackfillRepository;
use He4rt\IntegrationGithub\Backfill\RateLimit;
use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

/**
 * Roda o backfill de um repositório em segundo plano.
 *
 * O backfill faz centenas de requests REST ao GitHub e não cabe num request HTTP
 * (timeout). No worker ele é resumível por idempotência (re-executa da página 1 e
 * converge sem duplicar) e, ao bater rate limit, se re-agenda até o reset — sem
 * contar como falha. Erros transitórios (ex.: timeout de conexão) re-tentam com
 * backoff até o limite de exceções; o resto é encerrado em failed().
 */
#[Backoff([10, 30, 60])]
#[MaxExceptions(maxExceptions: 3)]
#[Timeout(timeout: 600)]
final class BackfillGithubRepository implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public function __construct(public GithubRepository $repository) {}

    public function uniqueId(): string
    {
        return $this->repository->getKey();
    }

    public function retryUntil(): DateTimeInterface
    {
        return Date::now()->addHours(6);
    }

    public function handle(BackfillRepository $backfill): void
    {
        try {
            $backfill->execute($this->repository);
        } catch (RequestException $requestException) {
            // Rate limit não é falha: re-agenda o job até o reset e converge depois.
            if (RateLimit::matches($requestException)) {
                $this->release(RateLimit::secondsUntilReset($requestException));

                return;
            }

            throw $requestException;
        }

        $this->repository->forceFill(['last_backfilled_at' => Date::now()])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Backfill do GitHub falhou', [
            'repository' => $this->repository->full_name,
            'error' => $exception->getMessage(),
        ]);
    }
}
