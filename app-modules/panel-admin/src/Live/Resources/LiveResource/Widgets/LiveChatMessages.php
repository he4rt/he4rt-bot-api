<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Live\Resources\LiveResource\Widgets;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Chat\Actions\DeleteChatMessage;
use He4rt\Live\Models\Live;
use Illuminate\Database\Eloquent\Builder;

class LiveChatMessages extends TableWidget
{
    /** Set by `Filament\Schemas\Components\Livewire::getComponentProperties()`. */
    public ?Live $record = null;

    protected ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Chat')
            ->query($this->messagesQuery(...))
            ->columns([
                TextColumn::make('provider.user.username')
                    ->label('Autor')
                    ->placeholder('—'),

                TextColumn::make('content')
                    ->label('Mensagem')
                    ->wrap(),

                TextColumn::make('sent_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone')),
            ])
            ->recordActions([
                Action::make('deleteChatMessage')
                    ->label('Remover')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Message $record): void {
                        /** @var User $moderator */
                        $moderator = auth()->user();

                        resolve(DeleteChatMessage::class)->execute($record, $moderator);

                        Notification::make()
                            ->success()
                            ->title('Mensagem removida')
                            ->send();
                    }),
            ]);
    }

    /** @return Builder<Message> */
    private function messagesQuery(): Builder
    {
        return Message::query()
            ->where('channel_id', $this->record?->getKey())
            ->latest('sent_at');
    }
}
