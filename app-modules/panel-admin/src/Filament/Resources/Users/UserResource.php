<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\EditUser;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\ListUsers;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\ViewUser;
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\ProvidersRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Users\Schemas\UserForm;
use He4rt\PanelAdmin\Filament\Resources\Users\Schemas\UserInfolist;
use He4rt\PanelAdmin\Filament\Resources\Users\Tables\UsersTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'username';

    /**
     * Conta nasce por OAuth ({@see \He4rt\Identity\Auth\Actions\FindOrCreateUserByProvider}).
     * Criar à mão produziria usuário sem identidade externa, que não consegue logar.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProvidersRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // `external_identities.model_id` é varchar e `users.id` é uuid: uma
        // subquery correlacionada (withCount) falha no Postgres por falta de
        // cast. O eager load compara por valor bindado e funciona.
        return parent::getEloquentQuery()
            ->with(['profile', 'providers', 'roles']);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'name', 'email'];
    }

    /**
     * @param  User  $record
     * @return array<string, mixed>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            'Nome' => $record->name,
            'E-mail' => $record->email,
        ]);
    }
}
