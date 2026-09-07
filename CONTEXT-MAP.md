---
title: Mapa do Sistema
order: 5
---

# Context Map

This is a modular monorepo (`internachi/modular`). Each bounded context lives under `app-modules/` with its own `CONTEXT.md` and optional `docs/adr/`.

## Contexts

| Context             | Path                               | Description                                                                                                                     |
| ------------------- | ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| Moderation          | `app-modules/moderation/`          | Content moderation pipeline — classification, routing, enforcement, appeals                                                     |
| Bot Discord         | `app-modules/bot-discord/`         | Discord bot runtime (Laracord websocket, slash commands, event handlers)                                                        |
| Integration Discord | `app-modules/integration-discord/` | Discord platform transport (REST API via Saloon), OAuth, ETL                                                                    |
| Identity            | `app-modules/identity/`            | Users, tenants, external identities, authentication                                                                             |
| Events              | `app-modules/events/`              | Event participation lifecycle — enrollment, check-in, attendance, XP dispatch                                                   |
| Gamification        | `app-modules/gamification/`        | Character progression — XP, levels, badges, seasons, daily bonuses                                                              |
| Panel Admin         | `app-modules/panel-admin/`         | Filament admin panel — dashboards, resources, moderation UI, marketing                                                          |
| Integration Twitch  | `app-modules/integration-twitch/`  | Twitch platform transport (Helix API via Saloon), OAuth, EventSub webhooks                                                      |
| Integration GitHub  | `app-modules/integration-github/`  | GitHub transport (REST via Saloon), OAuth, community contribution ingestion (backfill + webhooks) + event lake                  |
| Onboarding          | `app-modules/onboarding/`          | Universal, mandatory entry layer — polymorphic onboarding state machines by type; owns the per-type completion gate (APTO)      |
| Squads              | `app-modules/squads/`              | Squad lifecycle, membership and governance (captain/sub-captain, elections, removal, reallocation)                              |
| Marketing           | `app-modules/marketing/`           | Divulgação e sua medição — links curtos (`/l/{slug}`), destino versionado, captura crua de cliques                              |
| Contents            | `app-modules/contents/`            | Canonical catalogue of content published on external platforms (articles now, video next) — delegated types + provider contract |
| Live                | `app-modules/live/`                | Transmissões ao vivo da plataforma — ciclo de vida da live, stream keys, autorização do mediamtx, chat e audiência              |

## Relationships

```
┌─────────────────┐         ┌──────────────────────┐
│   Bot Discord   │         │ Integration Discord  │
│  (runtime/ws)   │────────▶│  (transport/rest)    │
└───┬─────────┬───┘         └──────────┬───────────┘
    │         │                        │
    │         │ dispatches             │ provides DiscordConnector
    │         │ CheckInRequested       │
    │         ▼                        │
    │   ┌─────────────────┐            │
    │   │     Events      │            │
    │   │ (participation) │────────────┼───── publishes domain events
    │   └────────┬────────┘            │            │
    │            │                     │            ▼
    │            │ reads users         │   ┌─────────────────┐
    │            │                     │   │  Gamification   │
    │            │                     │   │  (XP/levels)    │
    │            │                     │   └────────┬────────┘
    │            │                     │            │
    │ listens   │                      │            │ reads users
    │ to events │                      │            │
    ▼           ▼                      │            ▼
┌─────────────────┐                    │
│   Moderation    │◀───────────────────┘
│  (domain core)  │
└────────┬────────┘
         │ resolves identities
         ▼
┌─────────────────┐         ┌──────────────────────┐
│    Identity     │◀────────│ Integration Twitch   │
│ (users/tenants) │         │ (transport/webhooks) │
└─────────────────┘         └──────────────────────┘
```

### Dependency rules

- **Moderation** is platform-agnostic. It never imports from `bot-discord`, `integration-discord`, or `integration-twitch`.
- **Bot Discord** depends on Moderation (listens to domain events) and Integration Discord (uses transport) and Events (dispatches check-in domain events)..
- **Integration Discord** depends on Identity (OAuth user resolution). It never imports from Moderation.
- **Integration Twitch** depends on Identity (OAuth user resolution, ExternalIdentity for tenant linking). It never imports from Moderation, Integration Discord, or Bot Discord.
- **Integration GitHub** depends on Identity (OAuth user resolution; future `Character` seam via `ExternalIdentity`). It never imports from Activity, Economy, Moderation or any Bot/runtime module — it only emits the `GithubContributionRecorded` domain event. The community presentation (in `portal`) and the allowlist admin UI (in `panel-admin`) depend on it, never the reverse.
- **Identity** has no upstream dependencies on other contexts listed here.
- **Events** depends on Identity (reads Users and Tenants). Publishes domain events consumed by Gamification.
- **Gamification** depends on Identity (Character belongs to User). Listens to Events domain events for XP.
- **Onboarding** depends on Identity (User, tenant scoping, GitHub `ExternalIdentity` link) and listens to `integration-github`'s `GithubPullRequestApproved` domain event (reads the `challenge` repos in the allowlist). It never imports from `squads` — `squads` is a consumer of its completion gate, never the reverse.
- **Squads** depends on Onboarding (reads the `Squads`-completion gate, "APTO") and Identity (users/tenants). It never imports from presentation; the panels depend on it.
- **Marketing** owns short links and their click record. It depends only on **Identity** (`created_by` / `user_id` on a click) and on no other context. It renders no UI and registers no route: `portal` owns the public `/l/{slug}` edge and the "link unavailable" page, and `panel-admin` owns the staff CRUD and dashboards — both depend on `marketing`, never the reverse.
- **Contents** owns the canonical record of externally published content. It never talks HTTP (the `integration-*` modules implement its provider contract) and never awards anything — it emits `ArticlePublished` and `activity` decides. It depends on Identity (authorship resolution, `ExternalIdentityConnected`); `activity` and the panels depend on it, never the reverse.
- **Live** owns the live-broadcast lifecycle, stream keys, media-server authorization and audience. It depends on Identity (`He4rtLives` synthetic identities via `ResolveExternalIdentity`) and Activity (chat messages persist as `Message` rows, moderation trail as `moderation_events`) — domain→domain, sanctioned by the phase-2 spec. It renders no UI: `portal` owns the public `/live` page and `panel-admin` owns management, both depending on `live`, never the reverse. The mediamtx media server is infra (docker), not a module; it talks to `live` only via the auth hook and the signal webhook.
