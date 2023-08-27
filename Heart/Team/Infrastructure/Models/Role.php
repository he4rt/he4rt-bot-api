<?php

namespace Heart\Team\Infrastructure\Models;

use Heart\Team\Infrastructure\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'team_roles';

    protected $fillable = [
        'name',
        'description',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }
}
