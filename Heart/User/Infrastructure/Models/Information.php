<?php

namespace Heart\User\Infrastructure\Models;

use Heart\User\Infrastructure\Factories\InformationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Information extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'user_information';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'nickname',
        'linkedin_url',
        'github_url',
        'birthdate',
        'about',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): InformationFactory
    {
        return InformationFactory::new();
    }
}
