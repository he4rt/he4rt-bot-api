# Marketing Context

The system of record for **how the community's links are published and how that publication is
measured**. Today it owns one capability — short links — but the boundary is deliberately named for
the activity (divulgação), not the tool, so campaign grouping and channel analytics can land here
without a second module.

It is a **pure domain module**: no route, no Blade, no Filament. `portal` owns the public
`/l/{slug}` edge; `panel-admin` owns the staff UI. Both depend on this module; it depends on neither.

## Glossary

| Term                 | Definition                                                                                                                                                                                                                    | Not to be confused with                                                                                       |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| **ShortLink**        | The stable, public identity of a destination. Its `slug` is permanent and never reused, even after soft delete. Everything else about it — destination, UTM, tags, expiry — is mutable.                                       | The destination URL. The link outlives any particular destination.                                            |
| **Slug**             | The path segment after `/l/`. Always `{apelido}-{5 chars base36}`: the apelido is written by staff for legibility, the suffix is always generated for uniqueness. Canonically lowercase.                                      | A `Str::slug()` of the destination — the apelido is chosen, not derived.                                      |
| **Destination**      | Where a link points _right now_ (`short_links.destination_url`), plus the append-only history of where it pointed before (`short_link_destinations`, each row a closed or open `[valid_from, valid_until)` interval).         | A redirect. The destination is data; the redirect is what `portal` does with it.                              |
| **Click**            | One resolved request to `/l/{slug}` that produced a 302. Stored raw and indefinitely, including IP and full User-Agent. A blocked resolution (expired/disabled/unknown) is **not** a click and records nothing.               | A visit to the destination site — we only observe the hop.                                                    |
| **Bot click**        | A click whose User-Agent is classified as a bot by `matomo/device-detector` — overwhelmingly link-preview crawlers (Discord, WhatsApp, Twitter, Slack). Recorded with `is_bot = true` and excluded from `human_clicks_count`. | A fraudulent or malicious click. Unfurl bots are expected and benign.                                         |
| **Status**           | A link's derived state: `Active` · `Expired` (`expires_at` passed) · `Disabled` (`active = false` or soft-deleted). Computed from columns, never persisted.                                                                   | A column. There is no `status` field — deriving it keeps expiry correct without a scheduled job.              |
| **UTM (configured)** | The `utm_*` values stored on the link and appended to the destination on redirect, so the destination's own analytics also sees the origin.                                                                                   | The UTM recorded on a click — that is what arrived _in the short URL_, evidence of where the click came from. |
| **Tag**              | A free-form label on a link (`comunidade`, `hacktoberfest`), used to group and filter. No entity, no lifecycle.                                                                                                               | A campaign. A campaign would have a period and an objective; a tag is just a string.                          |

## What this module owns vs. what it does not

| Concern                                                | Here?                                                                      |
| ------------------------------------------------------ | -------------------------------------------------------------------------- |
| Creating, editing, expiring and disabling a short link | **Owns** — `ShortLink/Actions`                                             |
| Versioning the destination over time                   | **Owns** — every destination change closes the previous interval           |
| Deciding whether a slug may redirect                   | **Owns** — `ResolveShortLink` returns the resolution; it does not redirect |
| Recording and classifying a click                      | **Owns** — `RecordShortLinkClick` (queued)                                 |
| Issuing the HTTP 302, rendering the 404 page           | **No** — `portal`                                                          |
| Staff CRUD, tables, dashboards, charts                 | **No** — `panel-admin/src/Marketing`                                       |
| Who is allowed to create a link                        | **No** — `User::canAccessPanel()` already gates the admin panel            |

## Deliberate design decisions

- **302, never 301.** A cached permanent redirect would silently break both of this module's reasons
  to exist: the click would stop reaching the server (no measurement) and the visitor would keep
  going to the old destination after an edit (no mutability).
- **Raw click rows, no retention.** A conscious choice for maximum analytical freedom, with a known
  cost: `ip_address` and `user_agent` are personal data. See the spec's LGPD section — the privacy
  policy is an open debt, not a solved problem.
- **Status is derived, not stored.** Expiry therefore needs no scheduler and no cache invalidation.
- **Cache holds raw columns, not the verdict.** `ShortLinkCache` stores `expires_at` as data so the
  status is evaluated per request; only edits invalidate.
- **Clicks use `bigIncrements`, not UUID.** A divergence from the project default, justified by an
  append-only high-volume table where random UUIDs fragment the index.

## Structure

```
src/ShortLink/
├── Models/        ← ShortLink · ShortLinkClick · ShortLinkDestination
├── Enums/         ← ShortLinkStatus
├── ValueObjects/  ← UtmParameters · TagList
├── Casts/         ← AsUtmParameters · AsTagList
├── Actions/       ← CreateShortLink · UpdateShortLink · ResolveShortLink
├── DTOs/          ← NewShortLinkData · ShortLinkChanges · ClickContext · Resolution
├── Jobs/          ← RecordShortLinkClick
└── Support/       ← SlugGenerator · ShortLinkCache
```

## Module Boundaries

- Depends on **Identity** only (`User` for `created_by` and a click's optional `user_id`).
- Never imports from `portal`, `panel-admin`, or any `bot-*` / `integration-*` module.
- Registers no route and ships no view. If this module ever needs to render something, the
  boundary has been crossed.

See `docs/specs/2026-08-21-encurtador-de-links.md` for the full specification.
