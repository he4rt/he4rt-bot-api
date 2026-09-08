# Panel Admin — Context

The admin panel (`/admin`) is the operational hub for the He4rt Developers community. It is a Filament v5 panel that provides dashboards, resource management, and moderation tools for community administrators.

## Glossary

| Term                   | Definition                                                                                                                                                                       | Not to be confused with                                         |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| **Cluster**            | A Filament navigation group that acts as a sub-panel with its own sidebar. Implemented as a `Cluster` class with `$slug` and `$shouldRegisterSubNavigation = false`.             | Filament NavigationGroup (simpler, no sub-navigation)           |
| **Marketing**          | Cluster focused on community growth analytics — Discord activity dashboards, meeting showcase. Slug: `marketing`.                                                                | The marketing team (people); here it's a panel section          |
| **Discord Dashboard**  | A Filament Page under Marketing that surfaces Discord community metrics (messages, voice, users) with configurable time ranges and rolling comparisons.                          | The Discord bot itself (`bot-discord` module)                   |
| **Meeting Showcase**   | A Filament Page under Marketing that generates visual cards of meeting participants for social media sharing.                                                                    | The `meeting` domain module (data layer)                        |
| **Rolling comparison** | The pattern of subdividing a selected time range into equal sub-periods to show evolution. 14d→2×7d, 30d→4×7d, 90d→3×30d, etc.                                                   | Quarter-based comparison (fixed calendar periods)               |
| **Period breakdown**   | A widget showing sub-period metrics side by side with multiple visualization modes (summary, table, cards, bars, donut).                                                         | The timeline chart (shows daily granularity)                    |
| **Activity by DOW**    | Aggregated activity per day-of-week (Mon→Sun) with toggle between All/Messages/Voice. Uses stacked bar chart in "All" mode.                                                      | Heatmap (shows hour×day matrix)                                 |
| **Deck Builder**       | The retrospective editing screen: three columns (structure / preview / inspector). Occupies the resource's `edit` key at route `/{record}/deck`; there is no separate edit form. | The public retrospective deck (`portal` renders it)             |
| **Structure column**   | The Deck Builder's left column: cover, source blocks in editorial order (each with slide chips), closing. It only selects and reorders — it never saves text.                    | The `deck_config` VO (what the column reads and writes to)      |
| **Inspector**          | The Deck Builder's right column: a contextual form with four modes (cover, source block, slide, closing), each writing where the Phase 2 CRUD already wrote.                     | A Filament infolist (read-only)                                 |
| **Slide chip**         | A togglable entry in the structure column, one per slide `kind` a source can emit. On/off applies to the whole kind — `github.repos` hides every repository card.                | A single rendered slide (no stable per-instance identity)       |
| **Exclusion**          | A curated ref (`pr:142`, `actor:maria`) that a source drops inside `collect()`. Changes the DATA: the item leaves the slides **and** the numbers, so it requires republishing.   | Hiding a source or a slide kind (presentation only, re-derives) |

## Module boundaries

Panel Admin is a **view layer** module. It:

- **Reads from** `activity` (messages, voice), `identity` (external identities), `moderation` (cases, appeals, actions), `community` (retrospectives)
- **Writes only editorial state** it is the operator UI for: the retrospective's texts, period and
  `deck_config` curation. Everything that computes or freezes data goes through a domain Action
  (`PublishRetrospective`), never through the panel
- **Owns** its Filament Pages, Widgets, Resources, and Blade views
- **Does not** contain domain logic, models, or migrations

## Structure

```
panel-admin/
├── src/
│   ├── Marketing/
│   │   ├── MarketingCluster.php
│   │   ├── Pages/
│   │   │   ├── DiscordDashboard.php
│   │   │   └── MeetingShowcasePage.php
│   │   └── Widgets/
│   │       └── DiscordStatsWidget.php
│   ├── Moderation/
│   │   ├── ModerationCluster.php
│   │   ├── Pages/
│   │   ├── Resources/
│   │   ├── Widgets/
│   │   └── Livewire/
│   ├── Filament/Resources/
│   ├── Pages/
│   │   └── Dashboard.php
│   └── PanelAdminServiceProvider.php
├── resources/views/
│   ├── marketing/
│   └── moderation/
├── lang/{en,pt_BR}/
├── config/panel-admin.php
└── docs/adr/
```

## Navigation pattern

Each cluster has a dedicated navigation builder method in `PanelAdminServiceProvider`. When the URL path contains the cluster slug (e.g. `marketing/`), the sidebar switches to show a "Back to Admin" link + the cluster's sub-navigation. Default navigation shows all clusters as top-level items.

## Architectural decisions

Recorded in [`docs/adr/`](docs/adr/):

- [ADR-0001](docs/adr/0001-discord-dashboard-architecture.md) — Discord Dashboard: widget structure, rolling comparison pattern, query layer, timezone handling, component extensions
- [ADR-0002](docs/adr/0002-deck-builder-da-retrospectiva.md) — Deck Builder: three columns, preview through the real render path, curation via segregated interface
