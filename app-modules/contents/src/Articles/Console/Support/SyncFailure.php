<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Console\Support;

use He4rt\Contents\Enums\ContentProvider;
use Throwable;

final readonly class SyncFailure
{
    public function __construct(
        public ContentProvider $provider,
        public string $reference,
        public string $exceptionClass,
        public string $message,
    ) {}

    public static function fromThrowable(ContentProvider $provider, string $reference, Throwable $exception): self
    {
        return new self(
            provider: $provider,
            reference: $reference,
            exceptionClass: class_basename($exception),
            message: $exception->getMessage(),
        );
    }

    /** @return array<int, string> */
    public function toRow(): array
    {
        return [
            $this->provider->getLabel(),
            $this->reference,
            "<fg=red>{$this->exceptionClass}</>",
            $this->message === '' ? '—' : $this->message,
        ];
    }
}
