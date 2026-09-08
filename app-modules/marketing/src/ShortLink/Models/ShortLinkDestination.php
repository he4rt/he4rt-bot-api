<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Marketing\Database\Factories\ShortLinkDestinationFactory;
use He4rt\Marketing\ShortLink\Casts\AsUtmParameters;
use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of the append-only destination history of a short link.
 *
 * Each row is a `[valid_from, valid_until)` interval. A null `valid_until`
 * marks the current destination.
 *
 * @property string $id
 * @property string $short_link_id
 * @property string $destination_url
 * @property UtmParameters $utm
 * @property string|null $changed_by
 * @property CarbonInterface $valid_from
 * @property CarbonInterface|null $valid_until
 * @property CarbonInterface|null $created_at
 */
#[Table(name: 'marketing_short_link_destinations')]
#[UseFactory(factoryClass: ShortLinkDestinationFactory::class)]
final class ShortLinkDestination extends Model
{
    /** @use HasFactory<ShortLinkDestinationFactory> */
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    /** @return BelongsTo<ShortLink, $this> */
    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class, 'short_link_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function isCurrent(): bool
    {
        return $this->valid_until === null;
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'utm' => AsUtmParameters::class,
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }
}
