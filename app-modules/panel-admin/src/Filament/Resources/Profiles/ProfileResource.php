<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\PanelAdmin\Filament\Resources\Profiles\Pages\EditProfile;
use He4rt\PanelAdmin\Filament\Resources\Profiles\Pages\ListProfiles;
use He4rt\PanelAdmin\Filament\Resources\Profiles\Pages\ViewProfile;
use He4rt\PanelAdmin\Filament\Resources\Profiles\RelationManagers\ProfileSkillsRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Profiles\RelationManagers\WorkExperiencesRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Profiles\Schemas\ProfileForm;
use He4rt\PanelAdmin\Filament\Resources\Profiles\Schemas\ProfileInfolist;
use He4rt\PanelAdmin\Filament\Resources\Profiles\Tables\ProfilesTable;
use He4rt\Profile\Models\Profile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static ?string $slug = 'profiles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'nickname';

    /**
     * O perfil nasce junto do usuário, em {@see \He4rt\Identity\User\Observers\UserObserver}.
     * Criar um segundo viola o índice único em `user_id`.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Apagar quebra a invariante "todo usuário tem perfil" que o resto do
     * sistema assume ao ler `$user->profile`.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProfileSkillsRelationManager::class,
            WorkExperiencesRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListProfiles::route('/'),
            'view' => ViewProfile::route('/{record}'),
            'edit' => EditProfile::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->withCount('profileSkills');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nickname', 'headline', 'user.username'];
    }
}
