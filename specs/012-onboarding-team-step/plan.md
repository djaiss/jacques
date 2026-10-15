# Implementation Plan: Onboarding Step 3, Your Team

**Branch**: `2026-10-15-onboarding-team-step` | **Date**: 2026-10-15 | **Spec**: [spec.md](spec.md)

## Summary

The step 3 placeholder becomes the team list of the design, with a side panel to add, edit and
remove team members, choose the services each performs at the headquarters, and prepare an
invitation with a role. Nothing is emailed: a prepared invitation is a new record that a later
feature sends. The owner joins the headquarters and its services when step 2 completes. The
footer is step 2's, not sticky. Decisions: [research.md](research.md). Tables:
[data-model.md](data-model.md). Routes: [contracts/routes.md](contracts/routes.md).

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Alpine.js, Alpine Ajax and Tailwind already ship
- **Storage**: SQLite, MySQL, PostgreSQL; one new table, `staff_invitations`
- **Testing**: PHPUnit, following `.claude/rules/testing.md`
- **Target Platform**: web back office, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: step 3 renders in one request with team, location services and categories eager loaded, about 10 people and 20 services
- **Constraints**: company isolation on every route, no driver specific SQL, no email sent, no dashes in prose
- **Scale/Scope**: 1 migration, 1 model, 3 new actions, 4 actions changed, 2 controllers, 1 screen with 3 partials

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Every write is an action; the controller only sequences them in a transaction (R3) |
| II. Simplicity | Pass. One table, three small actions, no new abstraction; the preview is plain Alpine (R6) |
| III. Tested | Pass. Every new or changed action, the model, the view models and both controllers get a test |
| IV. Laravel conventions | Pass. New migration with `up()` only, foreign keys declared at the bottom |
| V. Localized and safe | Pass. Removal is confirmed in the panel (R8); every string through `__()` or `trans_choice()` |
| VI. Accessible | Pass, checked on desktop, tablet and phone with the keyboard (quickstart) |
| VII. One core | Justified gap: no API nor webhook exists yet (R10) |
| VIII. Short record | Pass. Spec 123 lines, every other artifact under its cap |
| Invariant D3 | Pass. A team member stays a staff record with at most one account; deletion happens before any appointment exists |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/012-onboarding-team-step/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
├── contracts/routes.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
database/migrations/2026_10_15_000002_create_staff_invitations_table.php
database/factories/StaffInvitationFactory.php
app/Models/                                      StaffInvitation (new); Staff gains invitation(); Role unchanged
app/Actions/                                     new: PrepareStaffInvitation, DestroyStaffInvitation, SyncStaffLocationServices
                                                 changed: CreateStaff, UpdateStaffInformation (unique email, R4;
                                                 invitation dropped with the email, R1), OfferServicesAtLocation
                                                 (returns what it offered), CompleteOnboardingStep (owner joins, R2)
app/Enums/UserActionEnum.php                     StaffInvitationPrepared, StaffInvitationDeleted, StaffServicesUpdated
app/Http/Controllers/App/Onboarding/             StaffController (index reads the new view model)
app/Http/Controllers/App/Onboarding/Team/        StaffController (new, create, edit, update, destroy)
app/ViewModels/Onboarding/                       TeamViewModel extends StepViewModel; StaffPanel
resources/views/app/onboarding/                  staff.blade.php, _team-row, _staff-panel, _team-footer
routes/web.php                                   routes of contracts/routes.md
lang/*.json                                      through jacques:localize
tests/Unit/{Actions,Models,ViewModels}/          mirroring the classes above
tests/Feature/Controllers/App/Onboarding/        both controllers, including another company's 404
```

**Structure Decision**: the existing Laravel layout; nothing new at the top of `app/`.

## Order of Work

1. Migration, `StaffInvitation`, factory, `Staff::invitation()`, `PrepareStaffInvitation`, `DestroyStaffInvitation`.
2. Unique email in `CreateStaff` and `UpdateStaffInformation`, invitation dropped with the email; `SyncStaffLocationServices`.
3. `OfferServicesAtLocation` return value and the owner joining in `CompleteOnboardingStep` (R2); existing tests adjusted.
4. View models, routes, controllers, then the Blade screen: list and footer (US1, US4), panel and removal (US2), invitation, role and preview (US3).
5. `jacques:localize`, Pint, PHPStan, quickstart.

## Complexity Tracking

No violation to justify.
