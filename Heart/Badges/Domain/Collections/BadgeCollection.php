<?php

namespace Heart\Badges\Domain\Collections;

use ArrayIterator;
use Heart\Badges\Domain\Entities\BadgeEntity;
use JsonSerializable;

class BadgeCollection extends ArrayIterator implements JsonSerializable
{
    public function add(BadgeEntity $badgeEntity): self
    {
        $this->append($badgeEntity);

        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
