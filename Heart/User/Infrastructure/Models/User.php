<?php

namespace Heart\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    protected $fillable = [
        'id',
        'username',
        'is_donator'
    ];

    public function address(): HasOne
    {
        return $this->hasOne(UserAddress::class);
    }

    public function information(): HasOne
    {
        return $this->hasOne(Information::class);
    }
}
