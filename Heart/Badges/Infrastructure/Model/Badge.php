<?php

namespace Heart\Badges\Infrastructure\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $table = 'badges';

    protected $fillable = [
        'provider',
        'name',
        'description',
        'image_url',
        'redeem_code',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];
}
