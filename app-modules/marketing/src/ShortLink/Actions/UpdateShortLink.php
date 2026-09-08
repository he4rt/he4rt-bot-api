<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Actions;

use He4rt\Marketing\ShortLink\DTOs\ShortLinkChanges;
use He4rt\Marketing\ShortLink\Exceptions\InvalidDestinationUrl;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Support\DestinationUrlValidator;
use He4rt\Marketing\ShortLink\Support\ShortLinkCache;
use Illuminate\Support\Facades\DB;

/**
 * Edits a short link and records a destination change as history.
 *
 * The open interval is closed at `now()` and the new one opens at the same
 * instant, so there is never a gap, an overlap, or two open rows. Without the
 * history, a click chart cannot tell which destination each click went to.
 */
final readonly class UpdateShortLink
{
    /**
     * @throws InvalidDestinationUrl
     */
    public function execute(ShortLink $link, ShortLinkChanges $changes): ShortLink
    {
        $newDestination = $changes->destinationUrl();

        if ($newDestination !== null) {
            DestinationUrlValidator::assert($newDestination);
        }

        $link = DB::transaction(function () use ($link, $changes): ShortLink {
            $destinationChanged = $changes->hasDestinationChange($link);

            $link->fill($changes->toAttributes())->save();

            if ($destinationChanged) {
                $changedAt = now();

                $link->destinations()
                    ->whereNull('valid_until')
                    ->update(['valid_until' => $changedAt]);

                $link->destinations()->create([
                    'destination_url' => $link->destination_url,
                    'utm' => $link->utm,
                    'changed_by' => $changes->changedBy ?? auth()->id(),
                    'valid_from' => $changedAt,
                    'valid_until' => null,
                ]);
            }

            return $link;
        });

        ShortLinkCache::forget($link->slug);

        return $link;
    }
}
