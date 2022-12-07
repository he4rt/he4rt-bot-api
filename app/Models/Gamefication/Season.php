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
        'starts_at',
        'ends_at',
        'participants_count',
        'messages_count'
    ];

    protected $casts = [
        'status' => 'bool',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'participants_count' => 'int',
        'messages_count' => 'int'
    ];

    public function scopeCurrentSeason(Builder $query): Builder
    {
        return $query
            ->whereDate('starts_at', '<=', Carbon::now()->format('Y-m-d'))
            ->whereDate('ends_at', '>=', Carbon::now()->format('Y-m-d'));
    }
}
