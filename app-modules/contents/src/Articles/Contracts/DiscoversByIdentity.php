<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Contracts;

use He4rt\Contents\Articles\DTOs\ArticleDTO;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

interface DiscoversByIdentity extends ArticleProvider
{
    /** @return iterable<ArticleDTO> */
    public function fetchForIdentity(ExternalIdentity $identity): iterable;
}
