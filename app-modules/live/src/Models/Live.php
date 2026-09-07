<?php

declare(strict_types=1);

namespace He4rt\Live\Models;

use Carbon\CarbonInterface;
use He4rt\Live\Database\Factories\LiveFactory;
use He4rt\Live\Enums\LiveStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property LiveStatus $status
 * @property string $stream_key
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $ended_at
 * @property int $peak_viewers
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: LiveFactory::class)]
#[Table(name: 'lives')]
final class Live extends Model
{
    /** @use HasFactory<LiveFactory> */
    use HasFactory;
    use HasUuids;

    /** @var array<string, mixed> */
    protected $attributes = [
        'peak_viewers' => 0,
    ];

    /** Chave de stream no formato que o OBS espera no campo "Chave de stream". */
    public function obsStreamKey(): string
    {
        return sprintf(
            '%s?user=%s&pass=%s',
            config()->string('live.path'),
            config()->string('live.ingest_user'),
            $this->stream_key,
        );
    }

    /** @param Builder<$this> $query */
    protected function scopeCurrent(Builder $query): void
    {
        $query->where('status', '!=', LiveStatus::Ended);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => LiveStatus::class,
            'stream_key' => 'encrypted',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
