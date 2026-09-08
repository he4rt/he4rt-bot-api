<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\ExternalIdentityResource;

/**
 * Só leitura: vincular e desvincular identidade é responsabilidade das Actions
 * do módulo identity (LinkExternalIdentity, AttachProviderToUser), que disparam
 * eventos de domínio.
 */
class ProvidersRelationManager extends RelationManager
{
    protected static string $relationship = 'providers';

    protected static ?string $title = 'Identidades externas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('provider')
            ->defaultSort('connected_at', 'desc')
            ->columns([
                TextColumn::make('provider')
                    ->label('Provider')
                    ->badge(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('external_account_id')
                    ->label('Conta externa')
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('connected_at')
                    ->label('Conectada em')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),

                TextColumn::make('disconnected_at')
                    ->label('Desconectada em')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->placeholder('Ativa'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Abrir')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(static fn (ExternalIdentity $record): string => ExternalIdentityResource::getUrl('edit', [
                        'record' => $record,
                    ])),
            ]);
    }
}
