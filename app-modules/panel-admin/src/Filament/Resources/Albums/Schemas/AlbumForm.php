<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Albums\Schemas;

use App\Support\UploadLimit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use He4rt\Events\Event\Models\Event;
use He4rt\Events\Gallery\Enums\PhotoConversion;
use He4rt\Events\Gallery\Models\Album;
use Illuminate\Support\Str;

final class AlbumForm
{
    public const int MAX_PHOTOS_PER_UPLOAD = 100;

    public static function configure(Schema $schema): Schema
    {
        $maxKilobytes = UploadLimit::kilobytes(PhotoConversion::maxKilobytes());

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label(__('panel-admin::albums.columns.title'))
                    ->required()
                    ->maxLength(200)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    })
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label(__('panel-admin::albums.columns.slug'))
                    ->required()
                    ->maxLength(120)
                    ->alphaDash()
                    ->unique(
                        table: Album::class,
                        column: 'slug',
                        ignoreRecord: true,
                    ),

                Select::make('event_id')
                    ->label(__('panel-admin::albums.columns.event'))
                    ->relationship('event', 'title')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        $event = $state !== null ? Event::query()->find($state) : null;

                        if (!$event instanceof Event) {
                            return;
                        }

                        if (blank($get('location'))) {
                            $set('location', $event->location);
                        }

                        if (blank($get('happened_at'))) {
                            $set('happened_at', $event->starts_at->toDateString());
                        }
                    })
                    ->helperText(__('panel-admin::albums.form.helpers.event')),

                DatePicker::make('happened_at')
                    ->label(__('panel-admin::albums.columns.happened_at'))
                    ->required()
                    ->native(condition: false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now()),

                TextInput::make('location')
                    ->label(__('panel-admin::albums.columns.location'))
                    ->nullable()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label(__('panel-admin::albums.columns.description'))
                    ->nullable()
                    ->maxLength(2_000)
                    ->rows(3)
                    ->columnSpanFull(),

                DateTimePicker::make('published_at')
                    ->label(__('panel-admin::albums.columns.published_at'))
                    ->nullable()
                    ->seconds(condition: false)
                    ->timezone(config()->string('app.display_timezone'))
                    ->helperText(__('panel-admin::albums.form.helpers.published_at'))
                    ->columnSpanFull(),

                // Sem imageAspectRatio() nem redimensionamento no browser: quem
                // enquadra é a conversão no servidor (ver ProfilePage, #458).
                SpatieMediaLibraryFileUpload::make('photos')
                    ->label(__('panel-admin::albums.columns.photos'))
                    ->collection(Album::PHOTOS)
                    ->conversion(PhotoConversion::Thumb->value)
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->image()
                    ->imageAspectRatio(ratio: null)
                    ->automaticallyCropImagesToAspectRatio(condition: false)
                    ->automaticallyResizeImagesToWidth(width: null)
                    ->automaticallyResizeImagesToHeight(height: null)
                    ->acceptedFileTypes(PhotoConversion::mimeTypes())
                    ->maxSize($maxKilobytes)
                    ->maxFiles(self::MAX_PHOTOS_PER_UPLOAD)
                    ->panelLayout('grid')
                    ->helperText(__('panel-admin::albums.form.helpers.photos', [
                        'max_files' => self::MAX_PHOTOS_PER_UPLOAD,
                        'max_mb' => round($maxKilobytes / 1_024, 1),
                    ]))
                    ->columnSpanFull(),
            ]);
    }
}
