<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkClick;
use Illuminate\Database\Eloquent\Builder;

/**
 * The raw log behind the aggregates: one row for each click.
 *
 * `ip_address` and `user_agent` are in the table but have no column here. They
 * are personal data and the project has no privacy policy yet. See ADR-0003.
 */
class RecentClicksTable extends TableWidget
{
    /** Set by `Filament\Schemas\Components\Livewire::getComponentProperties()`. */
    public ?ShortLink $record = null;

    /**
     * Set at mount by the page. A Livewire island only accepts serializable
     * data, so the filter cannot arrive through `pageFilters`. The island's
     * dynamic `key()` is what remounts this widget with a new value.
     */
    public bool $includeBots = false;

    protected ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-admin::marketing.short_links.widgets.recent_clicks.heading'))
            ->query($this->clicksQuery(...))
            ->defaultSort('clicked_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('clicked_at')
                    ->label(__('panel-admin::marketing.short_links.widgets.recent_clicks.columns.clicked_at'))
                    ->dateTime('d/m/Y H:i:s')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),

                TextColumn::make('referer')
                    ->label(__('panel-admin::marketing.short_links.widgets.top_referers.dimensions.referer'))
                    ->placeholder(__('panel-admin::marketing.short_links.placeholders.no_referer'))
                    ->limit(32)
                    ->tooltip(fn (ShortLinkClick $record): ?string => $record->referer),

                TextColumn::make('device')
                    ->label(__('panel-admin::marketing.short_links.widgets.recent_clicks.columns.device'))
                    ->state(fn (ShortLinkClick $record): string => collect([
                        $record->device_type,
                        $record->browser,
                        $record->os,
                    ])->filter()->implode(' / '))
                    ->placeholder(__('panel-admin::marketing.short_links.placeholders.unknown')),

                TextColumn::make('country_code')
                    ->label(__('panel-admin::marketing.short_links.widgets.top_referers.dimensions.country_code'))
                    ->badge()
                    ->color('gray')
                    ->placeholder(__('panel-admin::marketing.short_links.placeholders.unknown')),

                TextColumn::make('utm_source')
                    ->label(__('panel-admin::marketing.short_links.fields.utm_source'))
                    ->placeholder(__('panel-admin::marketing.short_links.placeholders.none')),

                TextColumn::make('is_bot')
                    ->label(__('panel-admin::marketing.short_links.widgets.recent_clicks.columns.origin'))
                    ->badge()
                    ->state(fn (ShortLinkClick $record): string => $record->is_bot
                        ? ($record->bot_name ?? __('panel-admin::marketing.short_links.widgets.recent_clicks.bot'))
                        : __('panel-admin::marketing.short_links.widgets.recent_clicks.human'))
                    ->color(fn (ShortLinkClick $record): string => $record->is_bot ? 'warning' : 'gray'),
            ])
            ->emptyStateIcon(Heroicon::OutlinedCursorArrowRays)
            ->emptyStateHeading(__('panel-admin::marketing.short_links.widgets.recent_clicks.empty_heading'))
            ->emptyStateDescription(__('panel-admin::marketing.short_links.widgets.recent_clicks.empty_description'));
    }

    /**
     * A real Eloquent query, not `records()`: it gives pagination and sorting
     * for free, and the `bigint` primary key already identifies each row.
     *
     * @return Builder<ShortLinkClick>
     */
    private function clicksQuery(): Builder
    {
        $query = ShortLinkClick::query()
            ->where('short_link_id', $this->record?->getKey());

        if (!$this->includeBots) {
            $query->where('is_bot', operator: false);
        }

        return $query;
    }
}
