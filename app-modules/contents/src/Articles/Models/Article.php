<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Models;

use Carbon\CarbonInterface;
use He4rt\Contents\Database\Factories\ArticleFactory;
use He4rt\Contents\Models\ContentEntry;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property string $id
 * @property string|null $description
 * @property int|null $reading_time_minutes
 * @property string|null $canonical_url
 * @property string|null $body_markdown
 * @property string|null $body_html
 * @property CarbonInterface|null $source_edited_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: ArticleFactory::class)]
#[Table(name: 'content_articles')]
final class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'description',
        'reading_time_minutes',
        'canonical_url',
        'body_markdown',
        'body_html',
        'source_edited_at',
    ];

    /** @return MorphOne<ContentEntry, $this> */
    public function entry(): MorphOne
    {
        return $this->morphOne(ContentEntry::class, 'contentable');
    }

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'reading_time_minutes' => 'integer',
            'source_edited_at' => 'datetime',
        ];
    }
}
