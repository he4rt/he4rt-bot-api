<?php

declare(strict_types=1);

namespace He4rt\Contents\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Tracking\Contracts\ContributionDetail;
use He4rt\Contents\Casts\AsTagList;
use He4rt\Contents\Data\TagList;
use He4rt\Contents\Database\Factories\ContentEntryFactory;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $contentable_type
 * @property string $contentable_id
 * @property string|null $author_id
 * @property string $author_handle
 * @property ContentProvider $provider
 * @property string $external_id
 * @property string $title
 * @property string $url
 * @property string|null $thumbnail_url
 * @property TagList $tags
 * @property CarbonInterface $published_at
 * @property int|null $reactions_count
 * @property int|null $comments_count
 * @property int|null $saves_count
 * @property CarbonInterface|null $metrics_synced_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: ContentEntryFactory::class)]
#[Table(name: 'content_entries')]
final class ContentEntry extends Model implements ContributionDetail
{
    /** @use HasFactory<ContentEntryFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'contentable_type',
        'contentable_id',
        'author_id',
        'author_handle',
        'provider',
        'external_id',
        'title',
        'url',
        'thumbnail_url',
        'tags',
        'published_at',
        'reactions_count',
        'comments_count',
        'saves_count',
        'metrics_synced_at',
    ];

    /** @return MorphTo<Model, $this> */
    public function contentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function contributionTitle(): string
    {
        return $this->title;
    }

    public function contributionContext(): string
    {
        return $this->provider->getLabel();
    }

    public function contributionUrl(): string
    {
        return $this->url;
    }

    protected static function newFactory(): ContentEntryFactory
    {
        return ContentEntryFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'provider' => ContentProvider::class,
            'tags' => AsTagList::class,
            'published_at' => 'datetime',
            'metrics_synced_at' => 'datetime',
            'reactions_count' => 'integer',
            'comments_count' => 'integer',
            'saves_count' => 'integer',
        ];
    }
}
