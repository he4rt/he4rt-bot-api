<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Marketing\Database\Factories\ShortLinkClickFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One click on `/l/{slug}` that produced a redirect.
 *
 * Rows are stored raw and with no retention policy. `ip_address` and
 * `user_agent` are personal data. See ADR-0003.
 *
 * The primary key is a `bigint`, not a UUID: this is a high-volume append-only
 * table where a UUID v4 would fragment the B-tree index.
 *
 * @property int $id
 * @property string $short_link_id
 * @property CarbonInterface $clicked_at
 * @property string $ip_address
 * @property string $user_agent
 * @property string|null $referer
 * @property string|null $country_code
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $os
 * @property bool $is_bot
 * @property string|null $bot_name
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $user_id
 */
#[Table(name: 'marketing_short_link_clicks')]
#[UseFactory(factoryClass: ShortLinkClickFactory::class)]
#[WithoutTimestamps]
final class ShortLinkClick extends Model
{
    /** @use HasFactory<ShortLinkClickFactory> */
    use HasFactory;

    /** @return BelongsTo<ShortLink, $this> */
    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class, 'short_link_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
            'is_bot' => 'boolean',
        ];
    }
}
