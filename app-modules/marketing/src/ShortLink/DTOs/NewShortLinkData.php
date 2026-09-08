<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\DTOs;

use Carbon\CarbonInterface;
use He4rt\Marketing\ShortLink\Support\FormPayloadNormalizer;
use He4rt\Marketing\ShortLink\ValueObjects\TagList;
use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;

/**
 * Everything needed to create a short link, independent of the caller.
 *
 * `nickname` is what staff typed, never the final slug. The Action builds the
 * slug, so no caller can choose one and bypass the random suffix.
 */
final readonly class NewShortLinkData
{
    public function __construct(
        public string $nickname,
        public string $destinationUrl,
        public ?UtmParameters $utm = null,
        public ?TagList $tags = null,
        public bool $active = true,
        public ?CarbonInterface $expiresAt = null,
        public ?string $createdBy = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromForm(array $data, ?string $createdBy = null): self
    {
        $nickname = $data['nickname'] ?? $data['apelido'] ?? '';
        $destination = $data['destination_url'] ?? '';
        $author = $createdBy ?? ($data['created_by'] ?? null);

        return new self(
            nickname: is_string($nickname) ? $nickname : '',
            destinationUrl: is_string($destination) ? $destination : '',
            utm: FormPayloadNormalizer::utm($data),
            tags: FormPayloadNormalizer::tags($data),
            active: (bool) ($data['active'] ?? true),
            expiresAt: FormPayloadNormalizer::date($data['expires_at'] ?? null),
            createdBy: is_string($author) ? $author : null,
        );
    }

    /**
     * Attributes for `ShortLink::create()`, without the slug.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'destination_url' => $this->destinationUrl,
            'utm' => $this->utm ?? UtmParameters::fromArray([]),
            'tags' => $this->tags ?? TagList::fromArray([]),
            'active' => $this->active,
            'expires_at' => $this->expiresAt,
            'created_by' => $this->createdBy,
        ];
    }
}
