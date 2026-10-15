# Implementation Plan: Service Management

**Branch**: `2026-10-14-service-management` | **Date**: 2026-10-14 | **Spec**: [spec.md](spec.md)

## Summary

Add the company catalogue (categories, services, steps), the services each location offers with
an optional price, and the team members who perform them there. Everything is models, migrations
and actions; screens come later. Decisions are in [research.md](research.md), tables in
[data-model.md](data-model.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel

**Primary Dependencies**: none new

**Storage**: SQLite, MySQL and PostgreSQL, five new migrations (not in production yet)

**Testing**: PHPUnit, Larastan level 5, Pint

**Target Platform**: web back office

**Project Type**: Laravel web application

**Performance Goals**: listing a location's offers is one query with its services and steps eager loaded

**Constraints**: a refusal or a record of another company answers as not found (`ModelNotFoundException`)

**Scale/Scope**: 5 models, 15 actions, a few dozen services per company

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: one action per operation, no controller yet |
| II. Simplicity | Pass: no new permission (R1), no step actions (R2), duration computed (R3) |
| III. Tested | Pass: every model, factory and action gets a test |
| IV. Laravel conventions | Pass: `up()` only, foreign keys declared at the bottom, no `constrained()` |
| V. Localized, safe | Pass: new log labels through `jacques:localize`; no screen, so no destructive UI yet |
| VI. Accessible | Not applicable: no screen |
| VII. One core | Justified: no public API and no webhooks exist yet; the actions are what they will call |
| VIII. Short record | Pass: all artifacts within their caps; no `contracts/` |
| Invariants | Pass: D5 steps kept, D8 rewritten before this plan, D22 kept (a price is shown, never charged) |

Re-checked after Phase 1: no change.

## Project Structure

### Documentation (this feature)

```text
specs/003-service-management/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
└── checklists/requirements.md
```

No `contracts/`: the feature exposes no route and no API.

### Source Code (repository root)

```text
database/migrations/   2026_10_14_000000 to 000004: service_categories, services, service_steps,
                       location_services, team_member_services
database/factories/    one per new model
app/Models/            ServiceCategory, Service, ServiceStep, LocationService, TeamMemberService (new);
                       Company, Location, User (+ relationships)
app/Actions/           CreateServiceCategory, UpdateServiceCategory, UpdateServiceCategoryPosition,
                       DestroyServiceCategory, CreateService, UpdateService, UpdateServicePosition,
                       DeactivateService, ActivateService, DestroyService,
                       CreateLocationService, UpdateLocationService, DestroyLocationService,
                       AssignTeamMemberService, RemoveTeamMemberService (new);
                       RemoveLocation (+ drops the person's team member services there)
app/Enums/             UserActionEnum (+ one case per new action)
lang/*.json            through jacques:localize
tests/Unit/            Models and Actions mirroring every file above
```

**Structure Decision**: existing folders only. Location offers mirror locations
(`CreateLocation`, `UpdateLocation`, `DestroyLocation`), team member services mirror role
assignment (`AssignRole`, `RemoveRole`). Every action follows authorize, sanitize, validate, write,
log, with `TextSanitizer` and `LogUserAction` on the `low` queue.

## Order of work

1. Migrations, models, factories, model tests.
2. Category actions, then service actions with steps and positions.
3. Location service actions and `resolvedPrice()`.
4. Team member service actions, and the cleanup in `RemoveLocation`.
5. `UserActionEnum` cases, `jacques:localize`, Pint, PHPStan, full suite, `migrate:fresh --seed`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| No API or webhook coverage (VII) | Neither exists yet | Building them is D27 work, out of scope |
