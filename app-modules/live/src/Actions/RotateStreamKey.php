<?php

declare(strict_types=1);

namespace He4rt\Live\Actions;

use He4rt\Live\Models\Live;
use Illuminate\Support\Str;

/** Gera uma nova stream key: a anterior deixa de valer na próxima conexão. */
final readonly class RotateStreamKey
{
    public function execute(Live $live): Live
    {
        $live->update(['stream_key' => Str::random(40)]);

        return $live->refresh();
    }
}
