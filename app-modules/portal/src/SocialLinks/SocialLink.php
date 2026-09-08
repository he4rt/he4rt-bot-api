<?php

declare(strict_types=1);

namespace He4rt\Portal\SocialLinks;

final readonly class SocialLink
{
    public function __construct(
        public string $key,
        public string $label,
        public string $url,
        public string $icon,
        public string $accent,
        public ?string $accentDark = null,
    ) {}
}
