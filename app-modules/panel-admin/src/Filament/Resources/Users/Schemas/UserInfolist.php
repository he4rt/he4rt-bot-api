<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Enums\UserSituation;
use He4rt\Identity\User\Models\User;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Conta')
                    ->icon(Heroicon::OutlinedUser)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('username')
                            ->label('Username')
                            ->copyable(),

                        TextEntry::make('name')
                            ->label('Nome'),

                        TextEntry::make('email')
                            ->label('E-mail')
                            ->copyable()
                            ->placeholder('Sem e-mail'),

                        IconEntry::make('is_donator')
                            ->label('Apoiador')
                            ->boolean(),

                        TextEntry::make('roles.name')
                            ->label('Papéis')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => UserRole::from($state)->getLabel())
                            ->color(fn (string $state): array => UserRole::from($state)->getColor())
                            ->placeholder('Nenhum'),

                        TextEntry::make('first_login_at')
                            ->label('Primeiro login')
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('Nunca'),

                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone')),
                    ]),

                Section::make('Situação')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->description('Somente leitura. Punições são aplicadas pelo fluxo de moderação.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('situation')
                            ->label('Situação')
                            ->badge()
                            ->state(fn (User $record): UserSituation => $record->situation),

                        TextEntry::make('suspended_until')
                            ->label('Suspenso até')
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('—'),

                        TextEntry::make('banned_at')
                            ->label('Banido em')
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('—'),
                    ]),

                Section::make('Perfil')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('profile.headline')
                            ->label('Headline')
                            ->placeholder('Não preenchido'),

                        TextEntry::make('profile.seniority_level')
                            ->label('Senioridade')
                            ->badge()
                            ->placeholder('—'),

                        IconEntry::make('profile.available_for_proposals')
                            ->label('Aberto a propostas')
                            ->boolean(),
                    ]),
            ]);
    }
}
