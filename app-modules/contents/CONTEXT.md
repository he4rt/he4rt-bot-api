# Contents Context

The system of record for **content published by the community on external platforms**. It owns the
canonical catalogue — what was written, by whom, where, and how it is doing — independently of
whether the author has a He4rt account and independently of any reward. Articles first; video
(YouTube and friends) is the next type the model is shaped for.

This module is **catalogue, not scoreboard**. It never awards coins or XP, never touches
`Character`, and never talks HTTP: providers hand it normalized data, it decides what is true, and
it announces the fact. Whether that fact is worth a reward is `activity`'s judgement.

> **Status:** design accepted, implementation pending. See
> `docs/specs/2026-08-19-modulo-contents-artigos.md`.

## Glossary

| Term                      | Definition                                                                                                                                                                                                               | Not to be confused with                                                                                                                |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------- |
| **ContentEntry**          | The root record of one published piece, one row in `content_entries`. Carries everything queryable across types (author, provider, title, url, published date, engagement counters) plus the single `contentable` morph. | A `Timeline` entry (`activity`) — that is content **authored on our platform**; a ContentEntry mirrors content published **elsewhere** |
| **Article**               | The article subtype, one row in `content_articles`, reached through `contentable`. Holds only what no other type has: description, reading time, canonical url, body.                                                    | The `ActivityType::Article` enum case — that names a _kind of contribution_, not a stored document                                     |
| **Delegated type**        | The modelling idiom: the root owns the one morph and points _down_ to the subtype, so everything else FKs to the root. Same pattern as `Timeline → postable → PostEntry`.                                                | Polymorphic ownership (`morphTo` upward) — here the root is the anchor, not the dependant                                              |
| **ContentProvider**       | The enum naming a source of content (`devto`, and only that today). Maps to an `IdentityProvider` **when one exists**, via `toIdentityProvider(): ?IdentityProvider`.                                                    | `IdentityProvider` — that is the vocabulary of _accounts a person connects_; an RSS feed is nobody's account                           |
| **Author handle**         | The author's username **at the provider** (`content_entries.author_handle`), recorded on every entry whether or not the author is known to us. It is the reconciliation key.                                             | `author_id` — that is the resolved He4rt `User`, and it is frequently null                                                             |
| **Orphan entry**          | A ContentEntry with `author_id IS NULL`: the content exists and is catalogued, but no He4rt account has been matched to its handle yet. A normal, expected state — not an error.                                         | A rejected or invalid entry — an orphan is fully valid content, merely unattributed                                                    |
| **Adoption**              | Setting `author_id` on an orphan once its author's identity appears. Triggered by `ExternalIdentityConnected`, which `ReconcileOrphanEntries` consumes.                                                                  | Account merge (`AccountsMerged`) — that reassigns content between two _existing_ users                                                 |
| **ArticleProvider**       | The base contract a provider implements. Declares only which `ContentProvider` it speaks for; all fetching lives in the capability interfaces below.                                                                     | The provider's transport class (`DevToApiClient`) — the provider is the mapping layer that sits on top of transport                    |
| **Capability**            | An optional interface a provider may implement: `DiscoversBySource`, `DiscoversByIdentity`, `HydratesDetail`. What a provider cannot do is expressed by _not implementing_, never by returning empty.                    | A config flag — capabilities are checked with `instanceof`, at the type level                                                          |
| **Discovery by source**   | Fetching everything a _source_ publishes (the org feed, an RSS feed). Sees content from authors we do not know — this is what produces orphans.                                                                          | Discovery by identity — that is scoped to one connected person                                                                         |
| **Discovery by identity** | Fetching one connected person's own publications using their stored credential (`/articles/me/published`). Reaches content published outside the organisation.                                                           | Discovery by source — that can never see a personal blog post                                                                          |
| **Shallow DTO**           | An `ArticleDTO` built from a listing payload, with `detailHydrated = false`. Its null body means _"the listing carries no body"_, never _"this article has no body"_.                                                    | A hydrated DTO — only a hydrated DTO is allowed to write the detail fields                                                             |
| **Hydration**             | Fetching the provider's per-item detail endpoint to obtain what the listing omits (body, saves). Decided by this module from `source_edited_at`, never by the provider.                                                  | Enrichment from our own data — hydration only ever re-reads the provider                                                               |
| **`source_edited_at`**    | The provider's own "last edited" timestamp, stored on the subtype. Comparing it to the listing's value is the sole trigger for hydration.                                                                                | `updated_at` — that records when _our_ row changed, including counter-only updates                                                     |
| **Engagement counters**   | `reactions_count`, `comments_count`, `saves_count` on the root, overwritten on each sync. **Nullable on purpose:** `null` means the provider does not measure it; `0` means measured and zero.                           | A time series — no history is kept; today's number replaces yesterday's                                                                |
| **`external_id`**         | The item's id at the provider. Unique together with `provider`, and the idempotency key of the whole ingest.                                                                                                             | `Interaction.external_ref` — that is a namespaced string (`devto:article:123`) serving `activity`'s own dedupe                         |
| **`ArticlePublished`**    | The domain event announcing that an article now has a known author. Emitted on creation-with-author and on adoption — **never** for an orphan.                                                                           | "the article was published at the provider" — publication there is upstream and may long precede this event                            |

## Adding a new provider

Every source of content is a case of `ContentProvider`, and **the enum is the only place a
provider is named**. Adding one is:

1. A new case in `He4rt\Contents\Enums\ContentProvider`, with the Filament contracts
   (`HasLabel`, `HasColor`, `HasDescription`) filled for it — every `match` is exhaustive with no
   `default`, so the compiler lists what you must answer.
2. Its `toIdentityProvider()` arm. Return `null` when the source is not an account a person can
   connect (a plain RSS feed) — that is a meaningful answer, not a gap: such content is catalogued
   but never becomes an `Interaction`.
3. A provider class in the matching `integration-*` module implementing `ArticleProvider` plus
   whichever capabilities it can honour, registered on the `ArticleProviderRegistry` in that
   module's ServiceProvider `boot()`.
4. **A row in this glossary if it introduces vocabulary** — a provider with a concept the others
   lack (a paywall state, a series, a co-author) needs the term defined here before it is coded.

Nothing in `contents` is edited to add a provider beyond the enum. If a change requires touching
the sync command or an Action to accommodate one provider, the contract is wrong — fix the
contract.

## Structure

```
src/
├── ContentsServiceProvider.php   ← morphMap + ArticleProviderRegistry singleton
├── Enums/                        ← ContentProvider
├── Models/                       ← ContentEntry (the root)
├── Data/ · Casts/                ← TagList + AsTagList (typed jsonb, per 06-typed-json-casts)
└── Articles/
    ├── Models/                   ← Article (the subtype)
    ├── Contracts/                ← ArticleProvider · DiscoversBySource ·
    │                               DiscoversByIdentity · HydratesDetail
    ├── ArticleProviderRegistry.php
    ├── DTOs/                     ← ArticleDTO · ArticleEngagementDTO
    ├── Actions/                  ← UpsertArticle · ReconcileOrphanEntries
    ├── Events/                   ← ArticlePublished
    └── Console/                  ← SyncArticlesCommand (`contents:sync-articles`)
```

## Module Boundaries

### This module owns:

- The canonical record of externally published content, and its identity (`provider` + `external_id`).
- The article ↔ author attribution, including the unattributed state and its later resolution.
- The provider contract, the capability vocabulary, and the registry that discovers implementations.
- The decision of _when_ to re-read a provider's detail endpoint.
- The current engagement figures, as last observed.

### This module does NOT own:

- **HTTP.** It never calls a provider. Transport and credentials stay in `integration-*`.
- **Reward.** Coins, XP, tiers and approval are `activity`/`economy`; this module only announces facts.
- **`Character`.** It resolves and stores a `User` and stops there — it never reads a Character to
  decide anything and never creates one.
- **Engagement history.** Only the latest values are kept; a time series would be a separate,
  additive concern.
- **UI.** No Filament resources, no pages. `portal` and the panels read from here.
- **Moderation of external content.** The catalogue mirrors the provider.

## Dependencies

- **Identity** — reads `User` and `ExternalIdentity` to resolve authorship; listens to
  `ExternalIdentityConnected` to adopt orphans. Never the reverse.
- **Activity** — depends on _this_ module: it listens to `ArticlePublished` and owns
  `ActivityType::Article`. `contents` does not import `activity`.
- **`integration-*`** — depend on this module to implement the provider contract. `contents` never
  imports them; it only ever sees the interfaces it defined.
- **Presentation** (`portal`, `panel-*`) — read from this module, never the reverse.

See `docs/specs/2026-08-19-modulo-contents-artigos.md` for the design and the alternatives that
were rejected.
