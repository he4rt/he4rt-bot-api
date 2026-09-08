<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Grupos da sidebar do painel admin. A ordem dos cases define a ordem dos
 * grupos.
 *
 * O painel monta a navegação com um {@see \Filament\Navigation\NavigationBuilder}
 * próprio, e nesse modo a propriedade `$navigationGroup` dos Resources é
 * ignorada pelo Filament. Este enum é a fonte de label e ícone; quem consome é
 * {@see \He4rt\PanelAdmin\PanelAdminServiceProvider::defaultNavigation()}.
 */
enum NavigationGroup implements HasIcon, HasLabel
{
    case People;

    case Content;

    public function getLabel(): string
    {
        return match ($this) {
            self::People => __('panel-admin::navigation.groups.people'),
            self::Content => __('panel-admin::navigation.groups.content'),
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::People => Heroicon::OutlinedUsers,
            self::Content => Heroicon::OutlinedNewspaper,
        };
    }
}
