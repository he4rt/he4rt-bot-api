<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Models;

use Carbon\Carbon;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\QrToken;
use He4rt\Events\Database\Factories\EnrollmentFactory;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $event_id
 * @property string $user_id
 * @property EnrollmentStatus $status
 * @property bool $is_public
 * @property int|null $waitlist_position
 * @property array<string, mixed>|null $application_data
 * @property string|null $rejection_reason
 * @property Carbon|null $enrolled_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $attended_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table(name: 'events_enrollments')]
final class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'event_id',
        'user_id',
        'is_public',
        'application_data',
        'rejection_reason',
        'enrolled_at',
        'confirmed_at',
        'checked_in_at',
        'attended_at',
        'cancelled_at',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_public' => true,
    ];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EnrollmentTransition, $this> */
    public function transitions(): HasMany
    {
        return $this->hasMany(EnrollmentTransition::class);
    }

    /** @return HasMany<CheckIn, $this> */
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /** @return HasOne<QrToken, $this> */
    public function qrToken(): HasOne
    {
        return $this->hasOne(QrToken::class);
    }

    protected static function newFactory(): EnrollmentFactory
    {
        return EnrollmentFactory::new();
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function confirmed(Builder $query): void
    {
        $query->where('status', EnrollmentStatus::Confirmed);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function waitlisted(Builder $query): void
    {
        $query->where('status', EnrollmentStatus::Waitlisted);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', [
            EnrollmentStatus::Confirmed,
            EnrollmentStatus::CheckedIn,
            EnrollmentStatus::Attended,
        ]);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'is_public' => 'boolean',
            'application_data' => 'array',
            'enrolled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'attended_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
