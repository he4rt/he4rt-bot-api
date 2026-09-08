<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Actions;

use He4rt\Marketing\ShortLink\DTOs\NewShortLinkData;
use He4rt\Marketing\ShortLink\Exceptions\InvalidDestinationUrl;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Support\DestinationUrlValidator;
use He4rt\Marketing\ShortLink\Support\ShortLinkCache;
use He4rt\Marketing\ShortLink\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Creates a short link and opens its first destination interval.
 *
 * Both rows are written in one transaction. A link without an open
 * `[valid_from, null)` interval makes every subsequent click impossible to
 * attribute to a destination.
 */
final readonly class CreateShortLink
{
    /**
     * @throws InvalidDestinationUrl
     */
    public function execute(NewShortLinkData $data): ShortLink
    {
        DestinationUrlValidator::assert($data->destinationUrl);

        $link = DB::transaction(function () use ($data): ShortLink {
            /** @var ShortLink $link */
            $link = ShortLink::query()->create([
                ...$data->toAttributes(),
                'slug' => SlugGenerator::for($data->nickname),
                'base_slug' => SlugGenerator::base($data->nickname),
            ]);

            $link->destinations()->create([
                'destination_url' => $link->destination_url,
                'utm' => $link->utm,
                'changed_by' => $data->createdBy,
                'valid_from' => now(),
                'valid_until' => null,
            ]);

            return $link;
        });

        ShortLinkCache::forget($link->slug);

        return $link;
    }
}
