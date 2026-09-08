<?php

declare(strict_types=1);

namespace He4rt\Profile\Data;

use He4rt\Profile\Enums\EmploymentType;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class WorkPreferences implements Arrayable, JsonSerializable
{
    /**
     * @param  list<EmploymentType>  $employmentTypes
     */
    public function __construct(
        public bool $hasDisability = false,
        public bool $willingToRelocate = false,
        public bool $isOpenToRemote = false,
        public array $employmentTypes = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function makeFromPayload(array $payload): self
    {
        $employmentTypes = [];

        foreach ((array) ($payload['employment_types'] ?? []) as $value) {
            $type = $value instanceof EmploymentType ? $value : EmploymentType::tryFrom((string) $value);

            if ($type instanceof EmploymentType) {
                $employmentTypes[] = $type;
            }
        }

        return new self(
            hasDisability: (bool) ($payload['has_disability'] ?? false),
            willingToRelocate: (bool) ($payload['willing_to_relocate'] ?? false),
            isOpenToRemote: (bool) ($payload['is_open_to_remote'] ?? false),
            employmentTypes: array_values(array_unique($employmentTypes, SORT_REGULAR)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'has_disability' => $this->hasDisability,
            'willing_to_relocate' => $this->willingToRelocate,
            'is_open_to_remote' => $this->isOpenToRemote,
            'employment_types' => array_map(
                static fn (EmploymentType $type): string => $type->value,
                $this->employmentTypes,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
