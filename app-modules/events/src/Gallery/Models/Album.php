<?php

declare(strict_types=1);

namespace He4rt\Events\Gallery\Models;

use Carbon\Carbon;
use He4rt\Events\Database\Factories\AlbumFactory;
use He4rt\Events\Event\Models\Event;
use He4rt\Events\Gallery\DTOs\Photo;
use He4rt\Events\Gallery\Enums\PhotoConversion;
use He4rt\Events\Gallery\Support\PhotosRelation;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Um momento da comunidade em fotos. Pode apontar para um evento cadastrado,
 * mas não precisa: confra, bastidores e encontros antigos também são álbuns.
 *
 * @property string $id
 * @property string|null $event_id
 * @property string $slug
 * @property string $title
 * @property string|null $location
 * @property Carbon $happened_at
 * @property string|null $description
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $photos_count
 */
#[UseFactory(factoryClass: AlbumFactory::class)]
#[Table(name: 'events_albums')]
final class Album extends Model implements HasMedia
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;

    public const string PHOTOS = 'photos';

    public const int CARD_HIGHLIGHTS = 3;

    protected $fillable = [
        'event_id',
        'slug',
        'title',
        'location',
        'happened_at',
        'description',
        'published_at',
    ];

    /**
     * As conversões ficam em fila de propósito: um álbum chega em lote, e
     * converter dezenas de fotos dentro do request estouraria o tempo dele.
     * Quem exibe cai no original enquanto a conversão não existe.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS)
            ->useDisk('public')
            ->acceptsMimeTypes(PhotoConversion::mimeTypes())
            ->registerMediaConversions(function (?Media $media = null): void {
                foreach (PhotoConversion::cases() as $conversion) {
                    $this->addMediaConversion($conversion->value)
                        ->fit($conversion->fit(), $conversion->width(), $conversion->height())
                        ->format('webp');
                }
            });
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Só as fotos, já na ordem do admin. Existe além de getMedia() para
     * withCount('photos') e para o whereHas da listagem pública.
     *
     * @return PhotosRelation<Media, $this>
     */
    public function photos(): PhotosRelation
    {
        $media = $this->media()->getRelated();

        $relation = new PhotosRelation(
            $media->newQuery(),
            $this,
            $media->qualifyColumn('model_type'),
            $media->qualifyColumn('model_id'),
            $this->getKeyName(),
        );

        return $relation
            ->where('collection_name', self::PHOTOS)
            ->orderBy('order_column');
    }

    /**
     * Fotos marcadas pela equipe para representar o álbum. Sem marcação, as
     * primeiras da ordem, para o card nunca sair vazio.
     *
     * @return Collection<int, Media>
     */
    public function highlights(): Collection
    {
        $photos = $this->getMedia(self::PHOTOS);

        $flagged = $photos->filter(fn (Media $media): bool => Photo::fromMedia($media)->highlight);

        $chosen = $flagged->isNotEmpty() ? $flagged : $photos->take(self::CARD_HIGHLIGHTS);

        return $chosen->values();
    }

    public function cover(): ?Media
    {
        return $this->highlights()->first();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function withPhotos(Builder $query): void
    {
        $query->whereHas('photos');
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function chronological(Builder $query): void
    {
        $query->latest('happened_at')->latest();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'happened_at' => 'date',
            'published_at' => 'datetime',
        ];
    }
}
