@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Triage Labels

The skills speak in terms of five canonical triage roles. This file maps those roles to the actual label strings used in this repo's issue tracker.

| Label in skills            | Label in our tracker | Meaning                                  |
| -------------------------- | -------------------- | ---------------------------------------- |
| `needs-triage`             | `needs-triage`       | Maintainer needs to evaluate this issue  |
| `needs-info`               | `needs-info`         | Waiting on reporter for more information |
| `ready-for-agent`          | `ready-for-agent`    | Fully specified, ready for an AFK agent  |
| `ready-for-human`          | `ready-for-human`    | Requires human implementation            |
| `wontfix`                  | `wontfix`            | Will not be actioned                     |

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), use the corresponding label string from this table.

---

# Type Labels

Issue type follows conventional commit prefixes.

| Label            | Meaning                          |
| ---------------- | -------------------------------- |
| `type:feat`      | New feature                      |
| `type:fix`       | Bug fix                          |
| `type:refactor`  | Code refactoring                 |
| `type:docs`      | Documentation                    |
| `type:prd`       | Product Requirements Document    |
| `type:chore`     | Maintenance / tooling            |

---

# Module Labels

Every issue must be tagged with the module(s) it affects. Labels follow the pattern `mod:<module-name>`, matching the directory name under `app-modules/`.

| Label                    | Module directory         | Description              |
| ------------------------ | ------------------------ | ------------------------ |
| `mod:activity`           | `activity`               | Activity tracking        |
| `mod:bot-discord`        | `bot-discord`            | Discord bot              |
| `mod:community`          | `community`              | Community features       |
| `mod:contents`           | `contents`               | Published content catalogue |
| `mod:economy`            | `economy`                | Economy/wallet system    |
| `mod:events`             | `events`                 | Events & participation   |
| `mod:gamification`       | `gamification`           | XP, levels, ranking      |
| `mod:he4rt`              | `he4rt`                  | Core/design system       |
| `mod:identity`           | `identity`               | Auth & user identity     |
| `mod:integration-devto`  | `integration-devto`      | Dev.to integration       |
| `mod:integration-discord`| `integration-discord`    | Discord OAuth/API        |
| `mod:integration-twitch` | `integration-twitch`     | Twitch integration       |
| `mod:marketing`          | `marketing`              | Encurtador, campanhas    |
| `mod:moderation`         | `moderation`             | Moderation pipeline      |
| `mod:panel-admin`        | `panel-admin`            | Admin Filament panel     |
| `mod:panel-app`          | `panel-app`              | User Filament panel      |
| `mod:portal`             | `portal`                 | Public portal / homepage |
| `mod:profile`            | `profile`                | User profiles            |
| `mod:docs`               | `docs`                   | Knowledge base docs      |

When creating an issue for a new module that has no label yet, create the label first (`gh label create "mod:<name>" --description "<short description>" --color "c2e0c6"`) and add a row to this table.

---

# Difficulty Labels

Every implementable issue should be tagged with a difficulty estimate.

| Label                | Estimate    | Meaning                                              |
| -------------------- | ----------- | ---------------------------------------------------- |
| `difficulty:trivial` | < 1 day     | Deletion, config changes, scripts                    |
| `difficulty:easy`    | 1-2 days    | Single model/action, well-defined scope              |
| `difficulty:medium`  | 3-5 days    | Multiple files, Filament UI, moderate complexity     |
| `difficulty:hard`    | 1-2 weeks   | Cross-module, complex logic, multiple panels         |
| `difficulty:epic`    | 2+ weeks    | Entire new system, major refactors                   |

Issues tagged `difficulty:trivial` or `difficulty:easy` should also receive the `good first issue` label to help new contributors find approachable work.

---

# Title Convention

Issue titles follow **conventional commits** with the module as scope:

```
<type>(<module>): <short description in English>
```

Examples:
- `feat(profile): public profile page with domain routing`
- `refactor(gamification): XP system redesign`
- `fix(bot-discord): slash command timeout on large guilds`
- `prd(events): participation module MVP`
