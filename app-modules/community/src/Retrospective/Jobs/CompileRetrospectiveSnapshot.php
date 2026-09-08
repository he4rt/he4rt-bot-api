<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Jobs;

use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Models\Retrospective;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Congela o snapshot de uma edição em segundo plano: coleta todas as fontes
 * sobre o período + filtros da edição e grava o resultado cru, promovendo o
 * status de "publicando" para "publicado". As agregações rodam em SQL escopadas
 * pela janela, mas somar todas as fontes de uma retro anual ainda pode ser
 * demorado — daí o job.
 */
#[Timeout(timeout: 600)]
final class CompileRetrospectiveSnapshot implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Retrospective $retrospective) {}

    public function uniqueId(): string
    {
        return $this->retrospective->getKey();
    }

    public function handle(CompileSnapshot $compile): void
    {
        $snapshot = $compile->execute(
            $this->retrospective->period(),
            $this->retrospective->filters(),
        );

        $this->retrospective->forceFill([
            'snapshot' => $snapshot,
            'status' => RetrospectiveStatus::Published,
            'published_at' => Date::now(),
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falha ao congelar o snapshot da retrospectiva', [
            'retrospective' => $this->retrospective->getKey(),
            'error' => $exception->getMessage(),
        ]);

        $this->retrospective->forceFill(['status' => RetrospectiveStatus::Draft])->save();
    }
}
