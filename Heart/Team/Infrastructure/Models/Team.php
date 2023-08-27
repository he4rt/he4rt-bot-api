<?php

namespace Heart\Team\Infrastructure\Models;

use Heart\Team\Infrastructure\Factories\TeamFactory;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'leader_id',
        'sub_leader_id',
    ];

    protected $appends = [
        'members_count',
    ];

    public function membersCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->members()->count()
        );
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id', 'id');
    }

    public function subLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sub_leader_id', 'id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    public function isLeadership(string $memberId): bool
    {
        return in_array($memberId, [$this->leader_id, $this->sub_leader_id]);
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
