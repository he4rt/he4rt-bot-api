<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use He4rt\Community\Retrospective\Actions\PublishRetrospective;
use He4rt\Community\Retrospective\Models\Retrospective;

/**
 * Filament Action que envolve a Domain Action PublishRetrospective, mantendo a
 * apresentação focada em UI. Despacha o congelamento do snapshot em segundo plano.
 */
final class PublishRetrospectiveAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Publicar')
            ->icon(Heroicon::OutlinedRocketLaunch)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Publicar retrospectiva')
            ->modalDescription('Congela o snapshot atual e publica a edição. Os números ficam fixos até uma nova publicação.')
            ->action(function (Retrospective $record): void {
                resolve(PublishRetrospective::class)->execute($record);

                Notification::make()
                    ->success()
                    ->title('Publicação iniciada')
                    ->body('O snapshot está sendo congelado em segundo plano.')
                    ->send();
            });
    }

    public static function getDefaultName(): string
    {
        return 'publish';
    }
}
