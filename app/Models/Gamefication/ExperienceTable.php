<?php

namespace App\Models\Gamefication;

use Illuminate\Database\Eloquent\Model;

class ExperienceTable extends Model
{
    protected $table = 'experience_table';

    public $timestamps = false;

    protected $fillable = [
        'required'
    ];


}
