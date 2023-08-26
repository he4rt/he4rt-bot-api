<?php

namespace Heart\Team\Infrastructure\Models;

use Heart\Team\Infrastructure\Factories\InviteFactory;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invite extends Model
{
    use HasFactory;

    protected $table = 'team_invites';

    protected $fillable = [
        'team_id',
        'member_id',
        'invited_by',
        'accepted_at',
    ];

    public $timestamps = [
        'accepted_at' => 'timestamp'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    protected static function newFactory(): InviteFactory
    {
        return InviteFactory::new();
    }
}
