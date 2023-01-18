<?php

namespace Heart\User\Infrastructure\Models;

use Heart\User\Infrastructure\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'username',
        'is_donator'
    ];

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function information(): HasOne
    {
        return $this->hasOne(Information::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
