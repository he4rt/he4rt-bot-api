<?php

namespace Heart\Meeting\Application;

use Heart\Meeting\Domain\Actions\PaginateMeetings as PaginateMeetingsAction;
use Heart\Shared\Domain\Paginator;

class PaginateMeetings
{
    public function __construct(private readonly PaginateMeetingsAction $paginateMeetingsAction)
    {
    }

    public function handle(): Paginator
    {
        return $this->paginateMeetingsAction->handle();
    }
}
