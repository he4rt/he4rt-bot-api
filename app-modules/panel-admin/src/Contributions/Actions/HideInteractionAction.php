<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use He4rt\Activity\Tracking\Actions\HideInteraction;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\User\Models\User;

final class HideInteractionAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Ocultar')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Ocultar contribuição')
            ->modalDescription('A contribuição deixa de aparecer no perfil. O registro é mantido e pode ser exibido de novo.')
            ->modalSubmitActionLabel('Ocultar')
            ->visible(static fn (Interaction $record): bool => $record->isVisible())
            ->action(static function (Interaction $record): void {
                /** @var User $actor */
                $actor = auth()->user();

                resolve(HideInteraction::class)->handle($record, $actor);

                Notification::make()
                    ->success()
                    ->title('Contribuição ocultada')
                    ->send();
            });
    }

    public static function getDefaultName(): string
    {
        return 'hide';
    }
}
