<?php

namespace Heart\Provider\Infrastructure\Models;

use Heart\Provider\Infrastructure\Factories\TokenFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $provider_id
 * @property string $access_token
 * @property string $refresh_token
 */
class Token extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'subscriber_providers_tokens';

    protected $fillable = [
        'id',
        'provider_id',
        'access_token',
        'refresh_token',
        'expires_in'
    ];

    // TODO: expires token should have an human interface and auto refresh

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    protected static function newFactory(): TokenFactory
    {
        return TokenFactory::new();
    }
}
