<?php

namespace Heart\Season\Infrastructure\Models;

use Heart\Badges\Infrastructure\Model\Badge;
use Heart\Meeting\Infrastructure\Models\Meeting;
use Heart\Season\Infrastructure\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'seasons';

   protected $fillable = [
       'id',
       'name',
       'description',
       'messages_count',
       'participants_count',
       'meeting_count',
       'badges_count',
       'started_at',
       'ended_at',
   ];

   public function badges(): HasMany
   {
       return $this->hasMany(Badge::class);
   }

   public function meetings(): HasMany
   {
       return $this->hasMany(Meeting::class);
   }

    protected static function newFactory(): SeasonFactory
    {
        return SeasonFactory::new();
    }
}
