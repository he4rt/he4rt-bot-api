<?php


namespace App\Models\Gamification;


use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $table = 'seasons';

    protected $fillable = [
        'name',
        'duration',
        'status'
    ];
}
