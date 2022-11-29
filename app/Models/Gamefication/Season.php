<?php

namespace App\Models\Gamefication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $table = 'seasons';

    protected $fillable = [
        'name',
        'start',
        'end',
        'status'
    ];

    protected $casts = [
        'status' => 'bool'
    ];
    // TODO: mudar campo status para is_over
    // TODO: mudar start status para started_at
    // TODO: mudar end status para ended_at

    public function seasonStatus($status)
    {
        $this->update([
            'status' => $status
        ]);
    }
}
