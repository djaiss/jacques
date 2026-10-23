# Implementation Plan: Clients screen

**Branch**: `2026-10-23-clients-screen` | **Date**: 2026-10-23 | **Spec**: [spec.md](spec.md)

## Summary

A read only list of the company's clients behind the Clients tile of the company menu. One query
loads the visible clients with their visit count, last visit and next appointment; search,
location, segments, counts and the page slice run in PHP, and the segment rule is shared with
`ClientIndicators` so the list and the client file agree. Decisions: [research.md](research.md).
Routes and view model: [contracts/routes.md](contracts/routes.md). No `data-model.md`: no schema
change, the entities are those of specs/021.

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Alpine.js, Alpine Ajax, Tailwind, existing Blade components
- **Storage**: SQLite, MySQL, PostgreSQL; no migration
- **Testing**: Pest, following `.claude/rules/testing.md`
- **Target Platform**: web back office, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: two queries per page plus the tab counts from the same collection; under one second for 10,000 clients (SC-002)
- **Constraints**: company isolation, self scope of `client.view`, no driver specific SQL, no dashes in prose
- **Scale/Scope**: 3 routes, 3 controllers, 1 view model, 1 view and its row partial, 1 helper method, the top bar

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Nothing is written, so no action; reading lives in the view model |
| II. Simplicity | Pass. One query and collection filters (R1); no scope, column or event added |
| III. Tested | Pass. View model, the three controllers, `segmentOf()` and the top bar get tests, including another company's clients and the self scope |
| IV. Laravel conventions | Pass. Enum route binding, `LengthAwarePaginator`, GET form |
| V. Localized and safe | Pass. Every string through `__()` or `trans_choice()`; dates in the company time zone; no destructive action |
| VI. Accessible | Pass, checked by quickstart scenario 8; tabs are links with `aria-current`, the next appointment state is written, not only coloured |
| VII. One core | Justified gap: no API in this feature (R9) |
| VIII. Short record | Pass. Every artifact under its cap |
| Invariants D22, D23 | Pass. No money shown (Clarifications); erased clients read "Erased client" and never match a search |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/022-clients-screen/
├── spec.md  plan.md  research.md  quickstart.md
├── contracts/routes.md
├── checklists/requirements.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
app/Helpers/ClientIndicators.php                  static segmentOf() used by segment() (R2)
app/ViewModels/Clients/ClientsViewModel.php       query, filters, tabs, rows, footer, paginator
app/Http/Controllers/App/Clients/                 ClientController, ClientSegmentController,
                                                  ClientNotRebookedController
app/View/Components/TopBarLayout.php              clientsUrl
resources/views/components/top-bar.blade.php      Clients tile reads clientsUrl
resources/views/app/clients/                      index, _row
routes/web.php                                    routes of contracts/routes.md
lang/*.json                                       through jacques:localize
tests/Unit/Helpers/ClientIndicatorsTest.php, tests/Unit/ViewModels/Clients/,
tests/Feature/Controllers/App/Clients/, tests/Browser/ClientsTest.php
```

**Structure Decision**: the existing Laravel layout, a `Clients` folder beside `Services` for the
view model and the controllers.

## Order of Work

1. `segmentOf()` and its test (foundation).
2. View model query, scope and rows; `ClientController`, route, view, menu tile (US1).
3. Search and location picker (US2).
4. Segment and not rebooked tabs, their controllers and counts (US3).
5. Pagination, `jacques:localize`, Pint, PHPStan, quickstart at three widths.

## Complexity Tracking

No violation to justify.
