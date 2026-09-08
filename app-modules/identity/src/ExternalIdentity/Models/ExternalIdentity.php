<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\Database\Factories\ExternalIdentityFactory;
use He4rt\Identity\ExternalIdentity\Casts\AsCredentials;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Enums\IdentityType;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $model_type
 * @property string $model_id
 * @property IdentityType $type
 * @property IdentityProvider $provider
 * @property CredentialsType $credentials_type
 * @property ClientAccessManager $credentials
 * @property string|null $external_account_id
 * @property string|null $connected_by
 * @property CarbonInterface|null $connected_at
 * @property CarbonInterface|null $disconnected_at
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read User|null $connectedByUser
 */
#[Appends([
    'messages_count',
])]
#[Table(name: 'external_identities')]
final class ExternalIdentity extends Model
{
    /** @use HasFactory<ExternalIdentityFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /** @return MorphTo<Model, $this> */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'model_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function connectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'external_identity_id');
    }

    public function isConnected(): bool
    {
        return $this->connected_at !== null && $this->disconnected_at === null;
    }

    protected static function newFactory(): ExternalIdentityFactory
    {
        return ExternalIdentityFactory::new();
    }

    /**
     * Identidade que uma pessoa realmente conectou, e não um registro criado por
     * ingestão. As datas sozinhas não separam os dois: a ETL também preenche
     * connected_at. O que só existe no fluxo OAuth é a credencial.
     *
     * @param  Builder<$this>  $query
     */
    protected function scopeActivelyConnected(Builder $query): void
    {
        $query
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->whereRaw("coalesce(credentials::jsonb->>'access_token', '') <> ''");
    }

    protected function getMessagesCountAttribute(): int
    {
        return $this->messages()->count();
    }

    protected function casts(): array
    {
        return [
            'type' => IdentityType::class,
            'provider' => IdentityProvider::class,
            'credentials_type' => CredentialsType::class,
            'credentials' => AsCredentials::class,
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
