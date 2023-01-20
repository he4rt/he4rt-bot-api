<?php

namespace Heart\Meeting\Infrastructure\Models;

use Heart\Meeting\Infrastructure\Factories\MeetingTypeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property int $week_day
 * @property \Carbon\Carbon $start_at
 */
class MeetingType extends Model
{
    use HasFactory;

    protected $table = 'meeting_types';

    protected $fillable = [
        'name',
        'week_day',
        'start_at',
    ];

    public function startAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->generateStartAt($value),
        );
    }

    private function generateStartAt(string $value)
    {
        $hours = (string) intdiv($value, 60);
        $minutes = (string) $value % 60;

        $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);

        return $hours . ':' . $minutes;
    }

    protected static function newFactory(): MeetingTypeFactory
    {
        return MeetingTypeFactory::new();
    }
}
