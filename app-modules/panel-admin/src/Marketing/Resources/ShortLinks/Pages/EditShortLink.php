<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Marketing\ShortLink\Actions\UpdateShortLink as UpdateShortLinkAction;
use He4rt\Marketing\ShortLink\DTOs\ShortLinkChanges;
use He4rt\Marketing\ShortLink\Exceptions\InvalidDestinationUrl;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\PanelAdmin\Marketing\Concerns\ResolvesCurrentUserId;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\ShortLinkResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * @property ShortLink $record
 */
class EditShortLink extends EditRecord
{
    use ResolvesCurrentUserId;

    protected static string $resource = ShortLinkResource::class;

    /**
     * Flattens `utm` and `tags` into the shape `FormPayloadNormalizer` reads.
     *
     * Both are Value Objects. The casts do not implement
     * `SerializesCastableAttributes` and the objects are not `Arrayable`, so
     * `attributesToArray()` returns objects that Livewire cannot dehydrate.
     *
     * The `utm` key has to go: `FormPayloadNormalizer::utm()` prefers it and
     * would ignore the flat fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ShortLink $record */
        $record = $this->getRecord();

        unset($data['utm']);

        return [
            ...$data,
            ...$record->utm->toArray(),
            'tags' => $record->tags->toArray(),
        ];
    }

    /**
     * The domain Action writes the record: it closes the previous destination
     * interval and opens the new one. A `$record->update($data)` here would
     * discard the history.
     *
     * @param  ShortLink  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return resolve(UpdateShortLinkAction::class)->execute(
                $record,
                ShortLinkChanges::fromForm($data, $this->currentUserId()),
            );
        } catch (InvalidDestinationUrl $invalidDestinationUrl) {
            throw ValidationException::withMessages([
                'data.destination_url' => $invalidDestinationUrl->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ShortLinkResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
