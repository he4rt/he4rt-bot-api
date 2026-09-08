<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * The state of a short link.
 *
 * It is never persisted. The `ShortLink::$status` accessor derives it from the
 * `deleted_at`, `active` and `expires_at` columns.
 */
enum ShortLinkStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Active = 'active';
    case Expired = 'expired';
    case Disabled = 'disabled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => __('marketing::enums.short_link_status.active.label'),
            self::Expired => __('marketing::enums.short_link_status.expired.label'),
            self::Disabled => __('marketing::enums.short_link_status.disabled.label'),
        };
    }

    /**
     * These are states, not levels, so the light-to-red ramp does not apply.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'warning',
            self::Disabled => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Active => __('marketing::enums.short_link_status.active.description'),
            self::Expired => __('marketing::enums.short_link_status.expired.description'),
            self::Disabled => __('marketing::enums.short_link_status.disabled.description'),
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Active => Heroicon::CheckCircle,
            self::Expired => Heroicon::Clock,
            self::Disabled => Heroicon::NoSymbol,
        };
    }

    public function isRedirectable(): bool
    {
        return match ($this) {
            self::Active => true,
            self::Expired, self::Disabled => false,
        };
    }
}
