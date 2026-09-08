<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Events;

use He4rt\Contents\Models\ContentEntry;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Anuncia que um artigo do acervo passou a ter autor conhecido. Nunca emitido para orfao.
 */
final class ArticlePublished
{
    use Dispatchable;

    public function __construct(
        public ContentEntry $entry,
    ) {}
}
