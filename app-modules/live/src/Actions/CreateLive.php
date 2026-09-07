<?php

declare(strict_types=1);

namespace He4rt\Live\Actions;

use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Exceptions\CurrentLiveAlreadyExists;
use He4rt\Live\Models\Live;
use Illuminate\Support\Str;

/** Cria uma live com stream key própria, garantindo uma única live corrente. */
final readonly class CreateLive
{
    public function execute(string $title, ?string $description): Live
    {
        if (Live::query()->current()->exists()) {
            throw CurrentLiveAlreadyExists::make();
        }

        return Live::query()->create([
            'title' => $title,
            'description' => $description,
            'status' => LiveStatus::Created,
            'stream_key' => Str::random(40),
        ]);
    }
}
