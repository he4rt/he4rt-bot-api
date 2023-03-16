<?php

namespace Heart\Season\Domain\Collections;

use ArrayIterator;
use Heart\Season\Domain\Entities\SeasonEntity;
use JsonSerializable;

class SeasonCollection extends ArrayIterator implements JsonSerializable
{
    public function add(SeasonEntity $seasonEntity): self
    {
        $this->append($seasonEntity);

        return $this;
    }

    public static function make(array $seasonsPayload): self
    {
        $seasons = [];
        foreach ($seasonsPayload as $seasonPayload) {
            $seasons[] = SeasonEntity::make($seasonPayload);
        }

        return new self($seasons);
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
