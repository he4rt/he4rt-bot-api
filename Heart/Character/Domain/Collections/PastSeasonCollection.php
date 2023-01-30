<?php

namespace Heart\Character\Domain\Collections;

use ArrayIterator;
use Heart\Character\Domain\Entities\PastSeasonEntity;
use JsonSerializable;

class PastSeasonCollection extends ArrayIterator implements JsonSerializable
{
    public function add(PastSeasonEntity $entity): static
    {
        $this->append($entity);

        return $this;
    }

    public static function make(array $payload): self
    {
        $pastSeasons = [];
        foreach ($payload as $pastSeason) {
            $pastSeasons[] = PastSeasonEntity::make($pastSeason);
        }

        return new self($pastSeasons);
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
