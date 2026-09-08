<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Contracts;

use He4rt\Contents\Articles\DTOs\ArticleDTO;

interface HydratesDetail extends ArticleProvider
{
    public function fetchDetail(ArticleDTO $shallow): ArticleDTO;
}
