<?php

namespace Heart\Season\Domain\Repositories;

use Heart\Season\Domain\Collections\SeasonCollection;
use Heart\Season\Domain\Entities\SeasonEntity;

interface SeasonRepository
{
    public function getAll(): SeasonCollection;

    public function getCurrent(): SeasonEntity;
}
