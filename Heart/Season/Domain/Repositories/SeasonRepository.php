<?php

namespace Heart\Season\Domain\Repositories;

use Heart\Season\Domain\Entities\SeasonEntity;

interface SeasonRepository
{
    public function getAll(): array;

    public function getCurrent(): SeasonEntity;
}
