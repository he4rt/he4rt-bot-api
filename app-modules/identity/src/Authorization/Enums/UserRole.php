<?php

declare(strict_types=1);

namespace He4rt\Identity\Authorization\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Papéis atribuíveis a um usuário pelo painel admin.
 *
 * O nome do case é o `name` da role no spatie/laravel-permission. Quem tem
 * super admin passa por cima de qualquer verificação de permissão via
 * `Gate::before` em {@see \He4rt\Identity\IdentityServiceProvider}.
 */
enum UserRole: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case SuperAdmin = 'super-admin';

    public const string GUARD = 'web';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
        };
    }

    /**
     * @return array<int|string, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::SuperAdmin => Color::Red,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Acesso total ao painel admin. Passa por cima de qualquer verificação de permissão.',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::SuperAdmin => Heroicon::OutlinedShieldCheck,
        };
    }
}
