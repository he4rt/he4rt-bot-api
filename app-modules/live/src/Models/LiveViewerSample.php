<?php

declare(strict_types=1);

namespace He4rt\Live\Models;

use Carbon\CarbonInterface;
use He4rt\Live\Database\Factories\LiveViewerSampleFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $live_id
 * @property int $viewers
 * @property CarbonInterface $sampled_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: LiveViewerSampleFactory::class)]
#[Table(name: 'live_viewer_samples')]
final class LiveViewerSample extends Model
{
    /** @use HasFactory<LiveViewerSampleFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<Live, $this> */
    public function live(): BelongsTo
    {
        return $this->belongsTo(Live::class);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return ['sampled_at' => 'datetime'];
    }
}
