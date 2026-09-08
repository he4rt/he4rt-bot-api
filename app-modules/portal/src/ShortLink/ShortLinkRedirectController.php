<?php

declare(strict_types=1);

namespace He4rt\Portal\ShortLink;

use He4rt\Marketing\ShortLink\Actions\ResolveShortLink;
use He4rt\Marketing\ShortLink\DTOs\ClickContext;
use He4rt\Marketing\ShortLink\Jobs\RecordShortLinkClick;
use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The HTTP edge of the shortener.
 *
 * The `marketing` module decides whether a slug can redirect. This controller
 * reads no column, checks no state and knows nothing about the cache.
 */
final readonly class ShortLinkRedirectController
{
    public function __construct(private ResolveShortLink $resolve) {}

    public function __invoke(Request $request, string $slug): RedirectResponse|Response
    {
        $resolution = $this->resolve->execute($slug);

        $id = $resolution->id;
        $destination = $resolution->destinationUrl;
        $utm = $resolution->utm;

        // `isRedirectable()` is the decision. The null checks are for the static
        // analyser: a redirectable Resolution always carries all three.
        $cannotRedirect = !$resolution->isRedirectable()
            || $id === null
            || $destination === null
            || !$utm instanceof UtmParameters;

        if ($cannotRedirect) {
            // One answer for all four dead outcomes: unknown, disabled, expired
            // and soft deleted. A different answer for each would make the route
            // a slug enumeration oracle.
            return response()->view('portal::short-link-unavailable', status: 404);
        }

        dispatch(new RecordShortLinkClick(ClickContext::fromRequest($request, $id)));

        /*
         * 302, never 301. A permanent redirect stays in the browser cache and
         * defeats both reasons the shortener exists: the click stops arriving,
         * and a new destination never reaches whoever already clicked.
         *
         * The incoming query goes into `appendTo` because a visitor who arrived
         * with `?utm_source=twitter` carries a more specific intent than the UTM
         * configured on the link.
         */
        return redirect()->away(
            $utm->appendTo($destination, $request->query->all()),
            status: 302,
        );
    }
}
