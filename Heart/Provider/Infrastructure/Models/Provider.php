<?php

namespace Heart\Provider\Infrastructure\Models;

use Heart\Provider\Infrastructure\Factories\ProviderFactory;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property User $user
 * @property Collection<Token> $tokens
 * @property string $user_id
 * @property string $provider_id
 * @property string $provider
 */
class Provider extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'providers';

    protected $fillable = [
        'id',
        'user_id',
        'provider',
        'provider_id',
        'email',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    protected static function newFactory(): ProviderFactory
    {
        return ProviderFactory::new();
    }
}
