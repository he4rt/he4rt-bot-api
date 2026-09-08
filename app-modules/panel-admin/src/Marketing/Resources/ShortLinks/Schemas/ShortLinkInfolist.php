<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Schemas;

use Carbon\CarbonImmutable;
use Closure;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use He4rt\Marketing\ShortLink\Enums\ShortLinkStatus;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkClick;
use He4rt\Marketing\ShortLink\Models\ShortLinkDestination;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\ViewShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets\ClicksOverTimeChart;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets\DeviceBreakdownChart;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets\RecentClicksTable;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets\TopReferersTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Number;

/**
 * The layout of the short link page.
 *
 * `Grid::make(4)` with spans of 3 and 1 gives a 75/25 split that stacks on a
 * narrow screen without a manual breakpoint.
 *
 * The numbers are `TextEntry`, not a `StatsOverviewWidget`, because the infolist
 * is a schema of the page itself: a `state()` closure can read
 * `ViewShortLink::includeBots()` and answer the toggle without a remount. The
 * charts and tables are isolated Livewire islands and need the dynamic `key()`.
 */
class ShortLinkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(4)
                    ->columnSpanFull()
                    ->schema([
                        Group::make(self::analytics())->columnSpan(3),
                        Group::make(self::sidebar())->columnSpan(1),
                    ]),
            ]);
    }

    /**
     * The wide column: what the link is, then what it produced.
     *
     * @return array<int, mixed>
     */
    private static function analytics(): array
    {
        return [
            Section::make(__('panel-admin::marketing.short_links.sections.about'))
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    TextEntry::make('status')
                        ->label(__('panel-admin::marketing.short_links.fields.status'))
                        ->badge()
                        ->state(fn (ShortLink $record): ShortLinkStatus => $record->status),

                    TextEntry::make('creator.username')
                        ->label(__('panel-admin::marketing.short_links.fields.created_by'))
                        ->placeholder(__('panel-admin::marketing.short_links.placeholders.none')),

                    TextEntry::make('created_at')
                        ->label(__('panel-admin::marketing.short_links.fields.created_at'))
                        ->dateTime('d/m/Y H:i')
                        ->timezone(config('app.display_timezone')),

                    TextEntry::make('expires_at')
                        ->label(__('panel-admin::marketing.short_links.fields.expires_at'))
                        ->dateTime('d/m/Y H:i')
                        ->timezone(config('app.display_timezone'))
                        ->placeholder(__('panel-admin::marketing.short_links.stats.never_expires')),

                    TextEntry::make('tags')
                        ->label(__('panel-admin::marketing.short_links.fields.tags'))
                        ->badge()
                        ->state(fn (ShortLink $record): array => $record->tags->toArray())
                        ->placeholder(__('panel-admin::marketing.short_links.placeholders.none'))
                        ->columnSpanFull(),
                ]),

            Section::make(__('panel-admin::marketing.short_links.sections.utm'))
                ->description(__('panel-admin::marketing.short_links.helpers.utm'))
                ->columnSpanFull()
                ->columns(1)
                ->compact()
                ->collapsed()
                ->schema([
                    TextEntry::make('utm.source')
                        ->label(__('panel-admin::marketing.short_links.fields.utm_source'))
                        ->state(fn (ShortLink $record): ?string => $record->utm->source)
                        ->placeholder(__('panel-admin::marketing.short_links.placeholders.none')),

                    TextEntry::make('utm.medium')
                        ->label(__('panel-admin::marketing.short_links.fields.utm_medium'))
                        ->state(fn (ShortLink $record): ?string => $record->utm->medium)
                        ->placeholder(__('panel-admin::marketing.short_links.placeholders.none')),

                    TextEntry::make('utm.campaign')
                        ->label(__('panel-admin::marketing.short_links.fields.utm_campaign'))
                        ->state(fn (ShortLink $record): ?string => $record->utm->campaign)
                        ->placeholder(__('panel-admin::marketing.short_links.placeholders.none')),

                    TextEntry::make('utm.term')
                        ->label(__('panel-admin::marketing.short_links.fields.utm_term'))
                        ->state(fn (ShortLink $record): ?string => $record->utm->term)
                        ->placeholder(__('panel-admin::marketing.short_links.placeholders.none')),

                    TextEntry::make('utm.content')
                        ->label(__('panel-admin::marketing.short_links.fields.utm_content'))
                        ->state(fn (ShortLink $record): ?string => $record->utm->content)
                        ->placeholder(__('panel-admin::marketing.short_links.placeholders.none')),
                ]),

            Section::make(__('panel-admin::marketing.short_links.sections.numbers'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('clicks_total')
                        ->label(__('panel-admin::marketing.short_links.stats.clicks'))
                        ->state(fn (ShortLink $record, ViewShortLink $livewire): string => self::number(
                            $livewire->includeBots() ? $record->clicks_count : $record->human_clicks_count,
                        ))
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Bold)
                        ->helperText(fn (ViewShortLink $livewire): string => $livewire->includeBots()
                            ? __('panel-admin::marketing.short_links.stats.including_bots')
                            : __('panel-admin::marketing.short_links.stats.humans_only')),

                    TextEntry::make('peak')
                        ->label(__('panel-admin::marketing.short_links.stats.peak'))
                        ->state(fn (ShortLink $record, ViewShortLink $livewire): string => self::peak($record, $livewire->includeBots())['value'])
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Bold)
                        ->helperText(fn (ShortLink $record, ViewShortLink $livewire): string => self::peak($record, $livewire->includeBots())['helper']),

                    TextEntry::make('top_source')
                        ->label(__('panel-admin::marketing.short_links.stats.top_source'))
                        ->state(fn (ShortLink $record, ViewShortLink $livewire): string => self::topSource($record, $livewire->includeBots())['value'])
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Bold)
                        ->helperText(fn (ShortLink $record, ViewShortLink $livewire): string => self::topSource($record, $livewire->includeBots())['helper']),
                ]),

            Livewire::make(ClicksOverTimeChart::class, self::islandData())
                ->key(fn (ViewShortLink $livewire): string => $livewire->islandKey('clicks-over-time'))
                ->columnSpanFull(),

            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Livewire::make(TopReferersTable::class, self::islandData())
                        ->key(fn (ViewShortLink $livewire): string => $livewire->islandKey('top-referers'))
                        ->columnSpan(1),

                    Livewire::make(DeviceBreakdownChart::class, self::islandData())
                        ->key(fn (ViewShortLink $livewire): string => $livewire->islandKey('device-breakdown'))
                        ->columnSpan(1),
                ]),

            Livewire::make(RecentClicksTable::class, self::islandData())
                ->key(fn (ViewShortLink $livewire): string => $livewire->islandKey('recent-clicks'))
                ->columnSpanFull(),
        ];
    }

    /**
     * The sidebar: the filter, and where the link has pointed.
     *
     * @return array<int, mixed>
     */
    private static function sidebar(): array
    {
        return [
            Section::make(__('panel-admin::marketing.short_links.sections.filter'))
                ->columnSpanFull()
                ->compact()
                ->schema([
                    // The page's own `filtersForm` schema. A second Toggle would
                    // create a second state path and two competing filters.
                    EmbeddedSchema::make('filtersForm'),
                ]),

            Section::make(__('panel-admin::marketing.short_links.sections.destination_history'))
                ->description(__('panel-admin::marketing.short_links.sections.destination_history_hint'))
                ->columnSpanFull()
                ->compact()
                ->schema([
                    RepeatableEntry::make('destinations')
                        ->hiddenLabel()
                        ->contained(condition: false)
                        ->state(fn (ShortLink $record) => $record->destinations()
                            ->orderByDesc('valid_from')
                            ->get())
                        ->schema([
                            TextEntry::make('valid_from')
                                ->hiddenLabel()
                                ->size(TextSize::Small)
                                ->color('gray')
                                ->formatStateUsing(self::validity(...)),

                            TextEntry::make('destination_url')
                                ->hiddenLabel()
                                ->fontFamily(FontFamily::Mono)
                                ->size(TextSize::Small)
                                // Without the limit the URL breaks mid-word.
                                ->limit(30)
                                ->tooltip(fn (?string $state): ?string => $state),
                        ]),
                ]),
        ];
    }

    /**
     * The mount parameters of an island. Filament passes `record` itself.
     * `includeBots` is frozen at mount and only changes when `key()` changes.
     *
     * @return Closure(ViewShortLink): array<string, mixed>
     */
    private static function islandData(): Closure
    {
        return static fn (ViewShortLink $livewire): array => [
            'includeBots' => $livewire->includeBots(),
        ];
    }

    /**
     * "Current since 12/03/2026" for the open interval, "12/03 → 04/07" for the
     * closed ones.
     */
    private static function validity(ShortLinkDestination $record): string
    {
        $timezone = config('app.display_timezone');
        $from = $record->valid_from->timezone($timezone)->format('d/m/Y');

        if ($record->isCurrent()) {
            return __('panel-admin::marketing.short_links.fields.valid_since', ['date' => $from]);
        }

        return $from.' → '.$record->valid_until?->timezone($timezone)->format('d/m/Y');
    }

    /**
     * The busiest day, in one query. Use `AT TIME ZONE` once: a double
     * conversion moves the day by three hours.
     *
     * @return array{value: string, helper: string}
     */
    private static function peak(ShortLink $record, bool $includeBots): array
    {
        /** @var object{day: string, total: int}|null $busiest */
        $busiest = self::clicksQuery($record, $includeBots)
            ->selectRaw('(clicked_at AT TIME ZONE ?)::date AS day', [config('app.display_timezone')])
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();

        if ($busiest === null) {
            return [
                'value' => self::number(0),
                'helper' => __('panel-admin::marketing.short_links.stats.no_clicks_yet'),
            ];
        }

        return [
            'value' => self::number((int) $busiest->total),
            'helper' => CarbonImmutable::parse($busiest->day)->format('d/m/Y'),
        ];
    }

    /**
     * The origin with the most clicks, and its share of the total.
     *
     * @return array{value: string, helper: string}
     */
    private static function topSource(ShortLink $record, bool $includeBots): array
    {
        $total = self::clicksQuery($record, $includeBots)->count();

        if ($total === 0) {
            return [
                'value' => __('panel-admin::marketing.short_links.placeholders.none'),
                'helper' => __('panel-admin::marketing.short_links.stats.no_clicks_yet'),
            ];
        }

        /** @var object{bucket: string, total: int}|null $top */
        $top = self::clicksQuery($record, $includeBots)
            ->selectRaw("COALESCE(NULLIF(referer, ''), ?) AS bucket", [
                __('panel-admin::marketing.short_links.placeholders.no_referer'),
            ])
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('bucket')
            ->orderByDesc('total')
            ->first();

        if ($top === null) {
            return [
                'value' => __('panel-admin::marketing.short_links.placeholders.none'),
                'helper' => __('panel-admin::marketing.short_links.stats.no_clicks_yet'),
            ];
        }

        $share = Number::percentage((int) $top->total / $total * 100, maxPrecision: 1);

        return [
            'value' => (string) $top->bucket,
            'helper' => __('panel-admin::marketing.short_links.stats.share', [
                'clicks' => self::number((int) $top->total),
                'share' => is_string($share) ? $share : '',
            ]),
        ];
    }

    /**
     * Aggregates go through the query builder: a `GROUP BY` row has no primary
     * key, so there is no model to hydrate.
     */
    private static function clicksQuery(ShortLink $record, bool $includeBots): QueryBuilder
    {
        return ShortLinkClick::query()
            ->where('short_link_id', $record->getKey())
            ->unless($includeBots, fn (Builder $query): Builder => $query->where('is_bot', operator: false))
            ->toBase();
    }

    private static function number(int $value): string
    {
        $formatted = Number::format($value);

        return is_string($formatted) ? $formatted : (string) $value;
    }
}
