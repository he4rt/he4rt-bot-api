<?php

namespace App\Models\Gamefication;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $table = 'seasons';

    protected $fillable = [
        'name',
        'description',
        'start_at',
        'ends_at',
        'participants_count',
        'messages_count'
    ];

    protected $casts = [
        'status' => 'bool',
        'start_at' => 'timestamp',
        'ends_at' => 'timestamp',
        'participants_count' => 'int',
        'messages_count' => 'int'
    ];

    public function scopeCurrentSeason(Builder $query): Builder
    {
        dd(1);
        return $query
            ->where('start_at', '>=', Carbon::now())
            ->where('ends_at', '<=', Carbon::now());
    }
}
