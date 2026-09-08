<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use He4rt\Marketing\ShortLink\Actions\CreateShortLink as CreateShortLinkAction;
use He4rt\Marketing\ShortLink\DTOs\NewShortLinkData;
use He4rt\Marketing\ShortLink\Exceptions\InvalidDestinationUrl;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\PanelAdmin\Marketing\Concerns\ResolvesCurrentUserId;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\ShortLinkResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * @property ShortLink $record
 */
class CreateShortLink extends CreateRecord
{
    use ResolvesCurrentUserId;

    protected static string $resource = ShortLinkResource::class;

    /**
     * The domain Action writes the record. A `ShortLink::create($data)` here
     * would skip the slug suffix, the destination validation and the first
     * destination interval.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return resolve(CreateShortLinkAction::class)->execute(
                NewShortLinkData::fromForm($data, $this->currentUserId()),
            );
        } catch (InvalidDestinationUrl $invalidDestinationUrl) {
            throw ValidationException::withMessages([
                'data.destination_url' => $invalidDestinationUrl->getMessage(),
            ]);
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('panel-admin::marketing.short_links.notifications.created.title'))
            ->body(__('panel-admin::marketing.short_links.notifications.created.body', [
                'url' => ShortLinkResource::shortUrl($this->record),
            ]));
    }

    protected function getRedirectUrl(): string
    {
        return ShortLinkResource::getUrl('view', ['record' => $this->record]);
    }
}
