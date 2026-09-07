<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Live\Resources\LiveResource\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use He4rt\Live\Actions\EndLive;
use He4rt\Live\Actions\RotateStreamKey;
use He4rt\Live\Console\SimulateLiveChatCommand;
use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Models\Live;
use He4rt\PanelAdmin\Live\Resources\LiveResource;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Widgets\LiveAudienceChart;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Widgets\LiveChatMessages;
use Illuminate\Support\Facades\Process;

/**
 * @property Live $record
 */
class ViewLive extends ViewRecord
{
    public bool $revealStreamKey = false;

    protected static string $resource = LiveResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detalhes')
                ->columns(3)
                ->schema([
                    TextEntry::make('title')
                        ->label('Título')
                        ->columnSpanFull(),

                    TextEntry::make('description')
                        ->label('Descrição')
                        ->columnSpanFull()
                        ->placeholder('—'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),

                    TextEntry::make('started_at')
                        ->label('Início')
                        ->dateTime('d/m/Y H:i')
                        ->timezone(config('app.display_timezone'))
                        ->placeholder('—'),

                    TextEntry::make('ended_at')
                        ->label('Encerramento')
                        ->dateTime('d/m/Y H:i')
                        ->timezone(config('app.display_timezone'))
                        ->placeholder('—'),

                    TextEntry::make('peak_viewers')
                        ->label('Pico de espectadores')
                        ->numeric(),
                ]),

            Section::make('Ingest (OBS)')
                ->description('Copie cada campo para o lugar correspondente em Configurações → Transmissão do OBS.')
                ->columns(1)
                ->schema([
                    TextEntry::make('rtmp_server')
                        ->label('Servidor')
                        ->state(fn (): string => config()->string('live.rtmp_server'))
                        ->copyable()
                        ->fontFamily(FontFamily::Mono),

                    TextEntry::make('obs_stream_key')
                        ->label('Chave de stream')
                        ->state(fn (Live $record): string => $this->revealStreamKey
                            ? $record->obsStreamKey()
                            : str_repeat('•', 12))
                        ->copyable()
                        ->copyableState(fn (Live $record): string => $record->obsStreamKey())
                        ->fontFamily(FontFamily::Mono)
                        ->suffixAction(
                            Action::make('toggleStreamKeyVisibility')
                                ->label('Mostrar/ocultar chave de stream')
                                ->icon(fn (): Heroicon => $this->revealStreamKey
                                    ? Heroicon::OutlinedEyeSlash
                                    : Heroicon::OutlinedEye)
                                ->action(function (): void {
                                    $this->revealStreamKey = !$this->revealStreamKey;
                                }),
                        ),
                ]),
        ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('endLive')
                ->label('Encerrar live')
                ->icon(Heroicon::OutlinedStopCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== LiveStatus::Ended)
                ->action(function (): void {
                    $this->record = resolve(EndLive::class)->execute($this->record);

                    Notification::make()
                        ->success()
                        ->title('Live encerrada')
                        ->send();
                }),

            Action::make('rotateStreamKey')
                ->label('Rotacionar stream key')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== LiveStatus::Ended)
                ->action(function (): void {
                    $this->record = resolve(RotateStreamKey::class)->execute($this->record);

                    Notification::make()
                        ->success()
                        ->title('Stream key rotacionada')
                        ->send();
                }),

            Action::make('simulateChat')
                ->label(fn (Live $record): string => $this->chatSimulationActive($record)
                    ? 'Parar simulação de comentários'
                    : 'Simular comentários')
                ->icon(fn (Live $record): Heroicon => $this->chatSimulationActive($record)
                    ? Heroicon::OutlinedStopCircle
                    : Heroicon::OutlinedChatBubbleLeftRight)
                ->color(fn (Live $record): string => $this->chatSimulationActive($record)
                    ? 'danger'
                    : 'gray')
                ->visible(fn (): bool => app()->isLocal())
                ->disabled(fn (Live $record): bool => $record->status === LiveStatus::Ended)
                ->action(function (Live $record): void {
                    $cacheKey = SimulateLiveChatCommand::cacheKey($record);

                    if ($this->chatSimulationActive($record)) {
                        SimulateLiveChatCommand::cacheStore()->put($cacheKey, value: false);

                        Notification::make()
                            ->success()
                            ->title('Simulação de comentários parada')
                            ->send();

                        return;
                    }

                    SimulateLiveChatCommand::cacheStore()->put($cacheKey, value: true);

                    Process::path(base_path())->run(sprintf(
                        'nohup php artisan live:simulate-chat %s >> %s 2>&1 &',
                        escapeshellarg($record->id),
                        escapeshellarg(storage_path('logs/chat-simulation.log')),
                    ));

                    Notification::make()
                        ->success()
                        ->title('Simulação de comentários iniciada')
                        ->send();
                }),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            LiveAudienceChart::class,
            LiveChatMessages::class,
        ];
    }

    private function chatSimulationActive(Live $record): bool
    {
        return SimulateLiveChatCommand::cacheStore()->get(SimulateLiveChatCommand::cacheKey($record)) === true;
    }
}
