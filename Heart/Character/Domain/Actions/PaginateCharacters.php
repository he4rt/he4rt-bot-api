<?php

namespace Heart\Character\Domain\Actions;

use Heart\Character\Domain\Repositories\CharacterRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginateCharacters
{
    public function __construct(private readonly CharacterRepository $characterRepository)
    {
    }

    public function handle(): LengthAwarePaginator
    {
        return $this->characterRepository->paginate();
    }
}
