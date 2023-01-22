<?php

namespace Heart\Badges\Infrastructure\Model;

use Heart\Badges\Infrastructure\Factories\BadgeFactory;
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

    protected static function newFactory(): BadgeFactory
    {
        return BadgeFactory::new();
    }
}
