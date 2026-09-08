# Squads Context

The system of record for squads: their lifecycle, membership and in-squad roles, plus an append-only
audit trail of membership events. Governance decisions (elections, removals, reallocation) happen
**off-system** (Discord/WhatsApp/conversation); this module records their _outcome_ and owns the
resulting state — it is a ledger, not a workflow engine. Entry into a squad (candidacy) is the one
flow it actively conducts.

## Glossary

| Term                          | Definition                                                                                                                                                                                       | Not to be confused with                                                           |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------- |
| **Squad**                     | A tenant-scoped crew with a clear objective. Lifecycle `status`: `draft` → `active` → `inactive` → `archived`. Created by a super-admin (the bottom-up proposal/validation happens off-system).  | A WhatsApp group (the informal precursor) — the Squad is the formalized record    |
| **SquadMember**               | A row in the `squad_members` pivot: one person's standing in one squad. Carries `role`, `joined_at`, `left_at`.                                                                                  | A community member generally — this is squad-scoped standing                      |
| **Role (in squad)**           | The `squad_members.role` enum: `Captain` · `SubCaptain` · `Member` · `ExMember`. Confers power on the platform: Captain/Sub manage their own squad.                                              | A governance role (super-admin) — that is platform-wide, config-driven            |
| **Captain / SubCaptain**      | The squad's leadership. They conduct their own squad on the platform: approve/reject candidacy, promote a sub, mark an `ExMember`.                                                               | The Head dos Squads (an off-system human role; in software it is the super-admin) |
| **ExMember**                  | A person who left (or was removed from) a squad. A role value, not a deletion. Does **not** count toward exclusivity. `left_at` dates the exit.                                                  | A `Member` on leave — there is no "paused membership" state                       |
| **Application (candidatura)** | An APTO person's request to join a squad (`squad_applications`, `pending`/`approved`/`rejected`). The captain decides; approval creates the membership in a transaction.                         | An onboarding (community/program entry) — that is upstream, in `onboarding`       |
| **Exclusivity**               | A person may hold at most one _active_ membership (role in `Captain`/`SubCaptain`/`Member`) across all squads. Enforced on join.                                                                 | A hard unique DB constraint — `ExMember` rows are allowed to pile up              |
| **Vacancy**                   | A squad with no active `Captain`. It is the **absence** of a Captain in the pivot, not a status of its own.                                                                                      | The `inactive` squad status (the whole squad is dormant)                          |
| **Super-admin**               | Platform governance authority, sourced from the `super-admin` role via `User::isSuperAdmin()`. Creates squads, sets captains, overrides any squad action. Stands in for the "Head/Gestão" roles. | A Captain — a Captain's power is scoped to their own squad                        |
| **APTO**                      | The gate this module consumes from `onboarding`: the person completed the `Squads` onboarding. Required to apply or to be in a squad.                                                            | An `active` Squad — APTO is about a person, not a squad                           |

## What this module records vs. conducts

| Flow (P.O. doc)             | In this module (model B — record-keeping)                                                                      |
| --------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Candidacy to existing squad | **Conducted**: application → captain decides → membership created (exclusivity checked).                       |
| Squad creation (bottom-up)  | **Recorded**: super-admin registers the squad (draft→active) with its captain. Proposal/validation off-system. |
| Captain election            | **Recorded**: runs off-system; the outcome is registered (set captain/sub via promote).                        |
| Captain exit                | **Recorded**: mark `ExMember` / promote the sub (sub assumes) or leave the seat vacant.                        |
| Captain removal             | **Recorded**: runs off-system (moderator→management→Head); outcome registered (mark ExMember).                 |
| Leadership reallocation     | **Recorded**: super-admin moves a leader to another squad.                                                     |

Human rules that stay **out of software** this delivery: ≥1-delivery eligibility, tie-break by Head,
single-candidate handling, proportional vote minimum, and the "no direct re-election" rule. They are
applied by humans off-system; only results are recorded. (The open "definition of a delivery" is thus
not blocking.)

## State & audit

- `squads` — squad state (`status`, `objective`, `slug`, tenant-scoped).
- `squad_members` — current standing per (squad, user): `role`, `joined_at`, `left_at`.
- `squad_membership_events` — append-only trail: `action` (`join`/`leave`/`promote`/`demote`/…),
  `actor_id`, `reason`, `occurred_at`, `metadata`. Answers "who held what, when, and why".
- `squad_applications` — candidacy requests and their decision.

## Structure (proposed)

```
src/
├── Models/      ← Squad · SquadMember · SquadMembershipEvent · SquadApplication
├── Enums/       ← SquadStatus · SquadRole · MembershipAction · ApplicationStatus
├── Actions/     ← CreateSquad · ApplyToSquad · DecideApplication · PromoteToSubCaptain ·
│                  AssignCaptain · MarkExMember · ReallocateLeader · ArchiveSquad
├── Policies/    ← SquadPolicy (captain/sub of the squad, or super-admin)
└── DTOs/
```

## Module Boundaries

### This module owns:

- Squad lifecycle, membership, in-squad roles, and the membership audit trail.
- The candidacy flow and exclusivity enforcement.

### This module does NOT own:

- The onboarding/APTO gate — it **reads** `Onboarding::isCompleted(user, tenant, Squads)` from `onboarding`.
- UI — Filament lives in `panel-app` (captain/member) and `panel-admin` (super-admin).
- Conducting elections/removals — those happen off-system; only outcomes are recorded.
- The squad game core (all-or-nothing project, scoring, season, redemption) — deferred, not here yet.

## Dependencies

- **Onboarding** — reads the `Squads`-completion gate (APTO). Never the reverse.
- **Identity** — users, tenants, and super-admin authority (the `super-admin` role).
- Presentation panels depend on this module, never the reverse.

See `docs/adr/0001-governanca-como-registro.md`.
