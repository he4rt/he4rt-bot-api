<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use He4rt\Marketing\ShortLink\Actions\UpdateShortLink;
use He4rt\Marketing\ShortLink\DTOs\ShortLinkChanges;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\PanelAdmin\Marketing\Concerns\ResolvesCurrentUserId;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\ShortLinkResource;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

/**
 * The analytics page of one short link.
 *
 * The bot toggle reaches each part of the page by two different routes:
 *
 * - The number entries live in the infolist, which is a schema of this page, so
 *   they read `includeBots()` directly and re-render for free.
 * - The charts and tables are `Filament\Schemas\Components\Livewire` islands,
 *   which only accept serializable data at mount. The filter reaches them as a
 *   mount parameter, and a change of `islandKey()` makes Livewire remount them.
 *
 * The islands therefore flicker on each toggle, and each widget's own filter
 * returns to its default.
 *
 * @property ShortLink $record
 */
class ViewShortLink extends ViewRecord
{
    use HasFiltersForm;
    use ResolvesCurrentUserId;

    public const string INCLUDE_BOTS = 'include_bots';

    protected static string $resource = ShortLinkResource::class;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            /*
             * Every breakpoint, not `->columns(1)`. `HasFiltersForm` applies
             * `['md' => 2, 'xl' => 3, '2xl' => 4]` before this method runs, and
             * `HasColumns::columns()` merges instead of replacing, so the wider
             * breakpoints would survive and split the sidebar into four columns.
             */
            ->columns(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 1, 'xl' => 1, '2xl' => 1])
            ->components([
                Toggle::make(self::INCLUDE_BOTS)
                    ->label(__('panel-admin::marketing.short_links.widgets.include_bots.label'))
                    ->helperText(__('panel-admin::marketing.short_links.widgets.include_bots.helper'))
                    ->default(state: false)
                    ->inline(condition: false),
            ]);
    }

    public function includeBots(): bool
    {
        return (bool) ($this->filters[self::INCLUDE_BOTS] ?? false);
    }

    /**
     * A key suffix that makes the Livewire islands remount when the filter
     * changes. With a static key the charts stop answering the toggle.
     */
    public function islandKey(string $name): string
    {
        return $name.'-'.($this->includeBots() ? 'with-bots' : 'humans');
    }

    public function getTitle(): string
    {
        return Str::after(ShortLinkResource::shortUrl($this->record), '://');
    }

    public function getSubheading(): string
    {
        return '↳ '.$this->record->destination_url;
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('panel-admin::marketing.short_links.actions.edit_destination')),

            Action::make('copy')
                ->label(__('panel-admin::marketing.short_links.actions.copy_url.label'))
                ->icon(Heroicon::OutlinedClipboardDocument)
                ->color('gray')
                ->actionJs(fn (): string => sprintf(
                    'window.navigator.clipboard.writeText(%s); $tooltip(%s, { theme: $store.theme, timeout: 1500 })',
                    Js::from(ShortLinkResource::shortUrl($this->record)),
                    Js::from(__('panel-admin::marketing.short_links.actions.copy_url.copied')),
                )),

            Action::make('toggleActive')
                ->label(fn (): string => $this->record->active
                    ? __('panel-admin::marketing.short_links.actions.disable.label')
                    : __('panel-admin::marketing.short_links.actions.enable.label'))
                ->icon(fn (): Heroicon => $this->record->active ? Heroicon::OutlinedPause : Heroicon::OutlinedPlay)
                ->color(fn (): string => $this->record->active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->record->active
                    ? __('panel-admin::marketing.short_links.actions.disable.heading')
                    : __('panel-admin::marketing.short_links.actions.enable.heading'))
                ->modalDescription(fn (): string => $this->record->active
                    ? __('panel-admin::marketing.short_links.actions.disable.body')
                    : __('panel-admin::marketing.short_links.actions.enable.body'))
                ->action($this->toggleActive(...)),
        ];
    }

    /**
     * The domain Action writes the record, so the observer clears the redirect
     * cache. A `$record->update()` here would leave `/l/{slug}` serving the old
     * state.
     */
    private function toggleActive(): void
    {
        $wasActive = $this->record->active;

        resolve(UpdateShortLink::class)->execute(
            $this->record,
            ShortLinkChanges::make(
                active: !$wasActive,
                changedBy: $this->currentUserId(),
            ),
        );

        Notification::make()
            ->success()
            ->title($wasActive
                ? __('panel-admin::marketing.short_links.notifications.disabled.title')
                : __('panel-admin::marketing.short_links.notifications.enabled.title'))
            ->send();
    }
}
