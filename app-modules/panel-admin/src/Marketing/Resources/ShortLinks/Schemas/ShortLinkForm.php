<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\ShortLinkResource;
use Illuminate\Support\Facades\DB;

class ShortLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('panel-admin::marketing.short_links.sections.link'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('nickname')
                            ->label(__('panel-admin::marketing.short_links.fields.nickname'))
                            ->helperText(__('panel-admin::marketing.short_links.helpers.nickname'))
                            ->required()
                            ->maxLength(60)
                            ->rule('regex:/^[\pL\pN\s_-]+$/u')
                            ->visibleOn('create')
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label(__('panel-admin::marketing.short_links.fields.short_url'))
                            ->helperText(__('panel-admin::marketing.short_links.helpers.slug'))
                            ->formatStateUsing(fn (?string $state): ?string => $state === null
                                ? null
                                : ShortLinkResource::shortUrl($state))
                            ->disabled()
                            ->dehydrated(condition: false)
                            ->copyable(copyMessage: __('panel-admin::marketing.short_links.actions.copy_url.copied'))
                            ->visibleOn('edit')
                            ->columnSpanFull(),

                        TextInput::make('destination_url')
                            ->label(__('panel-admin::marketing.short_links.fields.destination_url'))
                            ->helperText(__('panel-admin::marketing.short_links.helpers.destination_url'))
                            ->url()
                            ->required()
                            ->maxLength(2_048)
                            // Mirrors DestinationUrlValidator::ALLOWED_SCHEMES.
                            ->rule('url:http,https')
                            ->columnSpanFull(),

                        TagsInput::make('tags')
                            ->label(__('panel-admin::marketing.short_links.fields.tags'))
                            ->helperText(__('panel-admin::marketing.short_links.helpers.tags'))
                            ->suggestions(self::existingTags(...))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('panel-admin::marketing.short_links.sections.lifecycle'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('active')
                            ->label(__('panel-admin::marketing.short_links.fields.active'))
                            ->default(state: true)
                            ->inline(condition: false),

                        DateTimePicker::make('expires_at')
                            ->label(__('panel-admin::marketing.short_links.fields.expires_at'))
                            ->helperText(__('panel-admin::marketing.short_links.helpers.expires_at'))
                            ->seconds(condition: false)
                            ->timezone(config('app.display_timezone'))
                            ->native(condition: false),
                    ]),

                Section::make(__('panel-admin::marketing.short_links.sections.utm'))
                    ->description(__('panel-admin::marketing.short_links.helpers.utm'))
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('utm_source')
                            ->label(__('panel-admin::marketing.short_links.fields.utm_source'))
                            ->maxLength(255),

                        TextInput::make('utm_medium')
                            ->label(__('panel-admin::marketing.short_links.fields.utm_medium'))
                            ->maxLength(255),

                        TextInput::make('utm_campaign')
                            ->label(__('panel-admin::marketing.short_links.fields.utm_campaign'))
                            ->maxLength(255),

                        TextInput::make('utm_term')
                            ->label(__('panel-admin::marketing.short_links.fields.utm_term'))
                            ->maxLength(255),

                        TextInput::make('utm_content')
                            ->label(__('panel-admin::marketing.short_links.fields.utm_content'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Tags already in use, offered as suggestions.
     *
     * @return array<int, string>
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

        return $tags;
    }
}
