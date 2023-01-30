<?php

namespace Heart\User\Infrastructure\Models;

use Heart\Character\Infrastructure\Models\Character;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $username
 * @property bool $is_donator
 */
class User extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'username',
        'is_donator',
    ];

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function information(): HasOne
    {
        return $this->hasOne(Information::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function character(): HasOne
    {
        return $this->hasOne(Character::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
