<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Contracts;

use He4rt\Contents\Enums\ContentProvider;

interface ArticleProvider
{
    public function provider(): ContentProvider;
}
