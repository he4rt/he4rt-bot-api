<?php

declare(strict_types=1);

namespace He4rt\Live\DTOs;

/** Payload que o mediamtx envia ao hook de autenticação HTTP externa. */
final readonly class IngestAuthPayload
{
    public function __construct(
        public string $action,
        public string $path,
        public string $user,
        public string $password,
        public string $ip,
        public string $protocol,
        public string $query,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $string = static fn (string $key): string => is_string($payload[$key] ?? null) ? $payload[$key] : '';

        return new self(
            action: $string('action'),
            path: $string('path'),
            user: $string('user'),
            password: $string('password'),
            ip: $string('ip'),
            protocol: $string('protocol'),
            query: $string('query'),
        );
    }
}
