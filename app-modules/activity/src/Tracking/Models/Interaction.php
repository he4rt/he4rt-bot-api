<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Database\Factories\InteractionFactory;
use He4rt\Activity\Tracking\Contracts\ContributionDetail;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\AttributionMethod;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $external_identity_id
 * @property string $user_id
 * @property ActivityType $type
 * @property AttributionMethod $attributed_by
 * @property string|null $source_type
 * @property string|null $source_id
 * @property string|null $external_ref
 * @property CarbonInterface $occurred_at
 * @property CarbonInterface|null $hidden_at
 * @property string|null $hidden_by
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read ExternalIdentity $externalIdentity
 * @property-read User $user
 * @property-read User|null $hiddenByUser
 * @property-read Model|null $source
 */
#[UseFactory(factoryClass: InteractionFactory::class)]
#[Table(name: 'interactions')]
final class Interaction extends Model
{
    /** @use HasFactory<InteractionFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function externalIdentity(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function hiddenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Título, contexto e link vivem na origem, não aqui. O morph aponta para uma
     * string livre, então a origem pode não honrar o contrato — uma fonte antiga,
     * um registro apagado — e nesse caso a contribuição fica sem detalhe em vez
     * de derrubar quem a lê.
     */
    public function detail(): ?ContributionDetail
    {
        $source = $this->source;

        return $source instanceof ContributionDetail ? $source : null;
    }

    public function isVisible(): bool
    {
        return $this->hidden_at === null;
    }

    /**
     * @param  Builder<$this>  $query
     */
    protected function scopeVisible(Builder $query): void
    {
        $query->whereNull('hidden_at');
    }

    /**
     * @param  Builder<$this>  $query
     */
    protected function scopeHidden(Builder $query): void
    {
        $query->whereNotNull('hidden_at');
    }

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'attributed_by' => AttributionMethod::class,
            'occurred_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }
}
