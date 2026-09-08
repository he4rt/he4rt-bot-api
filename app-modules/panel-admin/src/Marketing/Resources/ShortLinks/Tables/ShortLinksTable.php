<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use He4rt\Marketing\ShortLink\Enums\ShortLinkStatus;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\ShortLinkResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;
use Illuminate\Support\Number;

class ShortLinksTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('slug')
                    ->label(__('panel-admin::marketing.short_links.fields.slug'))
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyableState(fn (ShortLink $record): string => ShortLinkResource::shortUrl($record))
                    ->copyMessage(__('panel-admin::marketing.short_links.actions.copy_url.copied'))
                    ->description(fn (ShortLink $record): string => ShortLinkResource::shortUrl($record)),

                TextColumn::make('destination_url')
                    ->label(__('panel-admin::marketing.short_links.fields.destination_url'))
                    ->limit(48)
                    ->searchable()
                    ->color('primary')
                    ->url(fn (ShortLink $record): string => $record->destination_url, shouldOpenInNewTab: true),

                TextColumn::make('tags')
                    ->label(__('panel-admin::marketing.short_links.fields.tags'))
                    ->badge()
                    ->state(fn (ShortLink $record): array => $record->tags->toArray())
                    ->placeholder(__('panel-admin::marketing.short_links.placeholders.none')),

                // `status` is an accessor, not a column: no sortable or searchable.
                TextColumn::make('status')
                    ->label(__('panel-admin::marketing.short_links.fields.status'))
                    ->badge()
                    ->state(fn (ShortLink $record): ShortLinkStatus => $record->status),

                TextColumn::make('human_clicks_count')
                    ->label(__('panel-admin::marketing.short_links.fields.clicks'))
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->description(fn (ShortLink $record): string => __(
                        'panel-admin::marketing.short_links.table.clicks_description',
                        [
                            'total' => Number::format($record->clicks_count),
                            'bots' => Number::format($record->clicks_count - $record->human_clicks_count),
                        ],
                    )),

                TextColumn::make('created_at')
                    ->label(__('panel-admin::marketing.short_links.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label(__('panel-admin::marketing.short_links.fields.created_by'))
                    ->placeholder(__('panel-admin::marketing.short_links.placeholders.none'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('panel-admin::marketing.short_links.filters.status'))
                    ->options(ShortLinkStatus::class)
                    ->query(self::applyStatusFilter(...)),

                SelectFilter::make('tag')
                    ->label(__('panel-admin::marketing.short_links.filters.tag'))
                    ->options(self::existingTags(...))
                    ->searchable()
                    ->query(self::applyTagFilter(...)),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('copy_url')
                    ->label(__('panel-admin::marketing.short_links.actions.copy_url.label'))
                    ->icon(Heroicon::OutlinedClipboardDocument)
                    ->color('gray')
                    ->iconButton()
                    ->actionJs(fn (ShortLink $record): string => sprintf(
                        'window.navigator.clipboard.writeText(%s); $tooltip(%s, { theme: $store.theme, timeout: 1500 })',
                        Js::from(ShortLinkResource::shortUrl($record)),
                        Js::from(__('panel-admin::marketing.short_links.actions.copy_url.copied')),
                    )),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * `status` is not a column, so each option becomes the condition that the
     * `ShortLink::$status` accessor would derive, in the same order.
     *
     * @param  Builder<ShortLink>  $query
     * @param  array<string, mixed>  $data
     * @return Builder<ShortLink>
     */
    private static function applyStatusFilter(Builder $query, array $data): Builder
    {
        $value = $data['value'] ?? null;
        $status = is_string($value) ? ShortLinkStatus::tryFrom($value) : null;

        return match ($status) {
            ShortLinkStatus::Active => $query->redirectable(),
            ShortLinkStatus::Expired => $query
                ->where('active', operator: true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()),
            ShortLinkStatus::Disabled => $query->where('active', operator: false),
            null => $query,
        };
    }

    /**
     * @param  Builder<ShortLink>  $query
     * @param  array<string, mixed>  $data
     * @return Builder<ShortLink>
     */
    private static function applyTagFilter(Builder $query, array $data): Builder
    {
        $tag = $data['value'] ?? null;

        return is_string($tag) && $tag !== ''
            ? $query->whereJsonContains('tags', $tag)
            : $query;
    }

    /**
     * @return array<string, string>
     */
    private static function existingTags(): array
    {
        /** @var array<int, string> $tags */
        $tags = DB::table((new ShortLink)->getTable())
            ->selectRaw('DISTINCT jsonb_array_elements_text(tags) AS tag')
            ->whereNull('deleted_at')
            ->orderBy('tag')
            ->pluck('tag')
            ->all();

        return array_combine($tags, $tags);
    }
}
