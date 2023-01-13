<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $table = "badges";

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'redeem_code',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function canClaim(): bool
    {
        return $this->attributes['active'];
    }
}
