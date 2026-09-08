<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ContentEntries\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use He4rt\Contents\Articles\Models\Article;
use He4rt\Contents\Models\ContentEntry;

/**
 * Título, provider, data e tempo de leitura vivem no cabeçalho da página
 * ({@see \He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages\ViewContentEntry}),
 * não aqui — repeti-los em campo era a maior fonte de ruído desta tela.
 */
class ContentEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                self::readingColumn(),
                self::sidebar(),
            ]);
    }

    private static function readingColumn(): Section
    {
        return Section::make('Artigo')
            ->icon(Heroicon::OutlinedDocumentText)
            ->columnSpan(2)
            ->schema([
                // No Dev.to a descrição é gerada a partir do início do corpo:
                // com o corpo hidratado, exibir as duas é repetir o mesmo texto.
                TextEntry::make('contentable.description')
                    ->hiddenLabel()
                    ->size(TextSize::Large)
                    ->color('gray')
                    ->visible(static fn (ContentEntry $record): bool => !self::hasBody($record))
                    ->placeholder('Sem descrição.')
                    ->columnSpanFull(),

                TextEntry::make('body')
                    ->hiddenLabel()
                    ->markdown()
                    ->state(static fn (ContentEntry $record): string => self::body($record))
                    ->placeholder('Corpo não hidratado — o sync só busca o detalhe sob demanda.')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * O corpo só existe quando o sync hidratou o detalhe do artigo — a
     * descoberta traz apenas os metadados da listagem do provider.
     */
    private static function body(ContentEntry $record): string
    {
        $article = $record->contentable;

        return $article instanceof Article
            ? (string) $article->body_markdown
            : '';
    }

    private static function hasBody(ContentEntry $record): bool
    {
        return self::body($record) !== '';
    }

    private static function sidebar(): Grid
    {
        return Grid::make(1)
            ->columnSpan(1)
            ->schema([
                Section::make()
                    ->schema([
                        ImageEntry::make('thumbnail_url')
                            ->hiddenLabel()
                            ->imageWidth('100%')
                            ->checkFileExistence(condition: false)
                            ->extraImgAttributes(['class' => 'rounded-lg'])
                            ->visible(static fn (ContentEntry $record): bool => filled($record->thumbnail_url))
                            ->columnSpanFull(),

                        // Só quando difere do endereço: no Dev.to as duas
                        // costumam ser idênticas, e repetir a URL foi o que
                        // mais poluiu a versão anterior desta tela.
                        TextEntry::make('contentable.canonical_url')
                            ->label('URL canônica')
                            ->visible(static fn (ContentEntry $record): bool => $record->contentable instanceof Article
                                && filled($record->contentable->canonical_url)
                                && $record->contentable->canonical_url !== $record->url)
                            ->columnSpanFull(),

                        TextEntry::make('tags')
                            ->label('Tags')
                            ->badge()
                            ->state(static fn (ContentEntry $record): array => $record->tags->toArray())
                            ->placeholder('Sem tags')
                            ->columnSpanFull(),
                    ]),

                Section::make('Autoria')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        TextEntry::make('author.username')
                            ->label('Autor vinculado')
                            ->icon(Heroicon::OutlinedCheckBadge)
                            ->visible(static fn (ContentEntry $record): bool => $record->author !== null),

                        TextEntry::make('author_handle')
                            ->label(static fn (ContentEntry $record): string => $record->author !== null
                                ? 'Handle no provider'
                                : 'Handle no provider (sem vínculo)')
                            ->color(static fn (ContentEntry $record): string => $record->author !== null
                                ? 'gray'
                                : 'warning'),
                    ]),

                Section::make('Engajamento')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->description(static fn (ContentEntry $record): string => $record->metrics_synced_at === null
                        ? 'Métricas nunca sincronizadas'
                        : sprintf('Sincronizado %s', $record->metrics_synced_at->diffForHumans()))
                    ->columns(1)
                    ->schema([
                        TextEntry::make('reactions_count')
                            ->label('Reações')
                            ->numeric(0)
                            ->placeholder('—'),

                        TextEntry::make('comments_count')
                            ->label('Comentários')
                            ->numeric(0)
                            ->placeholder('—'),

                        TextEntry::make('saves_count')
                            ->label('Salvos')
                            ->numeric(0)
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
