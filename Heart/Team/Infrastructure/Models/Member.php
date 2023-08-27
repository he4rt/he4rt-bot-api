<?php

namespace Heart\Team\Infrastructure\Models;

use Heart\Team\Infrastructure\Factories\MemberFactory;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    use HasFactory;

    protected $table = 'team_members';

    protected $fillable = [
        'team_id',
        'member_id',
        'role_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }
}
