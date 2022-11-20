<?php

namespace App\Models\Gamefication;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $table = "badges";

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];
}
