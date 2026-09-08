<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Actions;

use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Jobs\CompileRetrospectiveSnapshot;
use He4rt\Community\Retrospective\Models\Retrospective;

/**
 * Publica (ou republica) uma edição. Marca "publicando" e despacha o job que
 * congela o snapshot em segundo plano — coletar todas as fontes sobre a janela
 * pode ser pesado num range anual e estourar o timeout do request (ADR-0002).
 */
final readonly class PublishRetrospective
{
    public function execute(Retrospective $retrospective): void
    {
        $retrospective->forceFill(['status' => RetrospectiveStatus::Publishing])->save();

        dispatch(new CompileRetrospectiveSnapshot($retrospective));
    }
}
