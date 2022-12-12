<?php

namespace App\Models\Gamefication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $required
 */
class ExperienceTable extends Model
{
    use HasFactory;

    protected $table = 'experience_table';

    public $timestamps = false;

    protected $fillable = [
        'required'
    ];
}
