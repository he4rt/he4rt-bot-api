<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use He4rt\Activity\Tracking\Actions\UnhideInteraction;
use He4rt\Activity\Tracking\Models\Interaction;

final class UnhideInteractionAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Mostrar')
            ->icon(Heroicon::OutlinedEye)
            ->color('success')
            ->visible(static fn (Interaction $record): bool => !$record->isVisible())
            ->action(static function (Interaction $record): void {
                resolve(UnhideInteraction::class)->handle($record);

                Notification::make()
                    ->success()
                    ->title('Contribuição visível de novo')
                    ->send();
            });
    }

    public static function getDefaultName(): string
    {
        return 'unhide';
    }
}
