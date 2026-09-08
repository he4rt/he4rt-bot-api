<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Interactions;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\PanelAdmin\Filament\Resources\Interactions\Pages\ListInteractions;
use He4rt\PanelAdmin\Filament\Resources\Interactions\Tables\InteractionsTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InteractionResource extends Resource
{
    protected static ?string $model = Interaction::class;

    protected static ?string $slug = 'contributions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $modelLabel = 'contribuição';

    protected static ?string $pluralModelLabel = 'contribuições';

    /**
     * Contribuição é projetada de uma fonte externa, nunca criada à mão: um
     * registro sem par na origem não sobreviveria à próxima projeção.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Apagar é ilusório — a projeção recria a linha a partir do lake. Para tirar
     * do perfil existe a ocultação, que é reversível e registra quem decidiu.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return InteractionsTable::configure($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListInteractions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'externalIdentity', 'hiddenByUser']);
    }
}
