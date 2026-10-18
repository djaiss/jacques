# Implementation Plan: Team members screen

**Branch**: `2026-10-18-team-members-screen` | **Date**: 2026-10-18 | **Spec**: [spec.md](spec.md)

## Summary

Settings › Team members becomes the list of the design, with one address per tab, and a side
panel per person reached at its own URL. The panel gathers the account actions of specs/016, a
single role picker and the read only permissions of that role. D4 is amended to one role per
person, which `AssignRole` now enforces. Removing an account reuses `DestroyUser`. Decisions:
[research.md](research.md). Routes and panel data: [contracts/routes.md](contracts/routes.md). No
`data-model.md`: the only schema change is a unique index (R3).

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Alpine.js, Alpine Ajax, Tailwind, `x-side-modal` already ship
- **Storage**: SQLite, MySQL, PostgreSQL; `user_roles.user_id` made unique in its migration
- **Testing**: Pest, following `.claude/rules/testing.md`
- **Target Platform**: web back office, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: list and panel in one query set (staff, user, roles, invitation, role permissions eager loaded), about 10 people
- **Constraints**: company isolation on every route, last active owner kept, no driver specific SQL, no dashes in prose
- **Scale/Scope**: 1 migration edited, 5 actions changed, 2 new controllers, 1 removed, 1 view model rewritten, 1 screen

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Role, invitation and account changes go through existing actions; controllers validate and delegate |
| II. Simplicity | Pass. No new action (R3, R5); role cards in Alpine without requests (R6); tab view models share one parent (R2) |
| III. Tested | Pass. Changed actions, the view model and every controller get tests, including another company's staff |
| IV. Laravel conventions | Pass. Migration edited before production, `up()` only |
| V. Localized and safe | Pass. Cancel and Remove confirmed in the panel; counts through `trans_choice()` |
| VI. Accessible | Pass, checked by quickstart scenario 8; the selected role is announced, not shown by border alone |
| VII. One core | Justified gap: no API nor webhook exists yet (R7) |
| VIII. Short record | Pass. Every artifact under its cap |
| Invariant D3 | Pass. Removing an account keeps the staff record and its appointments |
| D4 | Amended first: one role per person; Owner keeps every permission and one active owner remains |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/017-team-members-screen/
├── spec.md  plan.md  research.md  quickstart.md
├── contracts/routes.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
docs/specs/decisions.md                            D4: one role per person
database/migrations/2026_08_01_000003_create_user_roles_table.php   unique user_id
app/Actions/AssignRole.php                         sync, last owner guard (R3)
app/Actions/AcceptStaffInvitation.php, CreateCompany.php   sync instead of add
app/Actions/SendStaffInvitation.php                optional role, prepares when needed (R4)
app/Actions/DestroyUser.php                        deletes the staff invitation too (R5)
app/ViewModels/Settings/Administration/TeamViewModel.php   rows, tabs, panel(); Team{SignsIn,Invited,NoAccount}ViewModel per tab
app/Http/Controllers/App/Settings/Administration/  TeamController@show; new TeamRoleController,
                                                   TeamAccountController; StaffInvitation*Controller
                                                   redirect to the panel; RevocationController removed
resources/views/app/settings/administration/team/  index, _row, _panel
routes/web.php                                     routes of contracts/routes.md
lang/*.json                                        through jacques:localize
tests/Unit/{Actions,ViewModels}/, tests/Feature/Controllers/App/Settings/Administration/
```

**Structure Decision**: the existing Laravel layout; nothing new at the top of `app/`.
`RevokeStaffInvitation` stays, unused by the screen, for the public API (D27).

## Order of Work

1. D4 amendment, unique index, `AssignRole` and the two other `sync` callers (US3 foundation).
2. `TeamViewModel` rows, counts, tabs and their controllers, list view (US1).
3. Panel URL, account section, invitation controllers redirected, `DestroyUser` change and `TeamAccountController` (US2).
4. Role cards, read only permissions, `TeamRoleController` (US3).
5. `jacques:localize`, Pint, PHPStan, quickstart on three widths.

## Complexity Tracking

No violation to justify.
