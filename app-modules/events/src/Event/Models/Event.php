<?php

declare(strict_types=1);

namespace He4rt\Events\Event\Models;

use Carbon\Carbon;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Database\Factories\EventFactory;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Enums\EventType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property EventType $event_type
 * @property string|null $location
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property EventStatus $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table(name: 'events')]
final class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'event_type',
        'location',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return HasOne<EnrollmentPolicy, $this> */
    public function enrollmentPolicy(): HasOne
    {
        return $this->hasOne(EnrollmentPolicy::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasMany<CheckInCode, $this> */
    public function checkInCodes(): HasMany
    {
        return $this->hasMany(CheckInCode::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->useDisk('public');
    }

    public function isPast(): bool
    {
        return $this->ends_at->isPast();
    }

    public function totalDays(): int
    {
        return (int) $this->starts_at->startOfDay()->diffInDays($this->ends_at->startOfDay()) + 1;
    }

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }

    /** @param  Builder<self>  $query */
    protected function scopePublished(Builder $query): void
    {
        $query->where('status', EventStatus::Published);
    }

    /** @param  Builder<self>  $query */
    protected function scopeViewableByParticipant(Builder $query): void
    {
        $query->whereIn('status', EventStatus::viewableByParticipant());
    }

    /** @param  Builder<self>  $query */
    protected function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>', now());
    }

    /** @param  Builder<self>  $query */
    protected function scopeActive(Builder $query): void
    {
        $query->where('status', EventStatus::Published)
            ->where('ends_at', '>', now());
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => EventStatus::class,
        ];
    }
}
