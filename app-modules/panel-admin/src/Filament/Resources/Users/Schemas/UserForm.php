<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Identificação')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255)
                            ->unique(),

                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255)
                            ->unique(),
                    ]),

                Section::make('Sinalizações')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Toggle::make('is_donator')
                            ->label('Apoiador')
                            ->helperText('Marca manual de apoiador. Não afeta permissões.'),
                    ]),

                Section::make('Papéis')
                    ->columnSpanFull()
                    ->columns(1)
                    ->description('Quem tem super admin passa por cima de qualquer verificação de permissão.')
                    ->schema([
                        CheckboxList::make('roles')
                            ->label('Papéis')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserRole::from($record->name)->getLabel())
                            ->descriptions(self::roleDescriptions(...))
                            ->disabled(self::isEditingSelf(...))
                            ->helperText(fn (?User $record): ?string => self::isEditingSelf($record) ? 'Você não pode alterar os próprios papéis.' : null),
                    ]),
            ]);
    }

    private static function isEditingSelf(?User $record): bool
    {
        return $record?->is(auth()->user()) ?? false;
    }

    /**
     * @return array<int, string>
     */
    private static function roleDescriptions(): array
    {
        return Role::query()
            ->where('guard_name', UserRole::GUARD)
            ->get()
            ->mapWithKeys(fn (Role $role): array => [(int) $role->getKey() => UserRole::from($role->name)->getDescription()])
            ->all();
    }
}
