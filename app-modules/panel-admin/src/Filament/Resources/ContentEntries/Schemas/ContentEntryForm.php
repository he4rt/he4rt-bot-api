<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ContentEntries\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Contents\Models\ContentEntry;

/**
 * Só o vínculo de autor é editável.
 *
 * {@see \He4rt\Contents\Articles\Actions\UpsertArticle} sobrescreve título,
 * url, tags, métricas e corpo a cada sincronização, mas preserva um
 * `author_id` já preenchido (`$authorId ?? $entry->author_id`). Editar
 * qualquer outro campo aqui seria desfeito na próxima execução do
 * `contents:sync-articles`.
 */
class ContentEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Autoria')
                    ->description('O sync resolve o autor pela identidade conectada no provider. Quando não encontra, o vínculo pode ser feito aqui — e sobrevive às próximas sincronizações.')
                    ->schema([
                        Select::make('author_id')
                            ->label('Autor')
                            ->relationship('author', 'username')
                            ->searchable()
                            ->preload()
                            ->helperText(static fn (ContentEntry $record): string => sprintf(
                                'Handle no provider: %s',
                                $record->author_handle,
                            )),
                    ]),
            ]);
    }
}
