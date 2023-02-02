<?php

namespace App\Models\User;

use App\Models\Gamefication\Season as SeasonModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Season extends Model
{
    use HasFactory;
    protected $table = "user_seasons";

    protected $fillable = [
        'user_id',
        'season_id',
        'level',
        'messages_count',
        'badges_count',
        'meetings_count',
        'experience',
        'ranking_position',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(SeasonModel::class);
    }
}
