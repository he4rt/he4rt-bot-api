<?php

namespace Heart\User\Domain\Entities;

class InformationEntity implements \JsonSerializable
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $name,
        private readonly string $nickname,
        private readonly string $linkedinUrl,
        private readonly string $githubUrl,
        private readonly string $birthdate,
        private readonly string $about,
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            userId: $payload['user_id'],
            name: $payload['name'],
            nickname: $payload['nickname'],
            linkedinUrl: $payload['linkedin_url'],
            githubUrl: $payload['github_url'],
            birthdate: $payload['birthdate'],
            about: $payload['about'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'linkedin_url' => $this->linkedinUrl,
            'github_url' => $this->githubUrl,
            'birthdate' => $this->birthdate,
            'about' => $this->about,
        ];
    }
}
