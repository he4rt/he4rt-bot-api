<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Contracts;

use He4rt\Contents\Articles\DTOs\ArticleDTO;

interface DiscoversBySource extends ArticleProvider
{
    /** @return iterable<ArticleDTO> */
    public function fetchFromSource(): iterable;
}
