# Implementation Plan: Onboarding Step 4, Availability

**Branch**: `2026-10-16-onboarding-availability-step` | **Date**: 2026-10-16 | **Spec**: [spec.md](spec.md)

## Summary

The step 4 placeholder "Appointments" becomes "Availability": the salon's weekly opening hours,
edited in the browser and stored on "Finish setup", and the team's working hours, either a copy of
the opening hours or a member's own week saved from a side panel. "Finish setup" stores both,
ends the onboarding and opens a new placeholder dashboard. Decisions: [research.md](research.md).
Tables: [data-model.md](data-model.md). Routes: [contracts/routes.md](contracts/routes.md).

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Alpine.js, Alpine Ajax and Tailwind already ship
- **Storage**: SQLite, MySQL, PostgreSQL; one new table, one column added to `location_staff`
- **Testing**: PHPUnit, following `.claude/rules/testing.md`
- **Target Platform**: web back office, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: step 4 renders in one request with the headquarters, its opening hours and its bookable team eager loaded; "Finish setup" for about 10 people stays one transaction
- **Constraints**: company isolation on every route, no driver specific SQL, wall clock times in the location's time zone (R1), no dashes in prose
- **Scale/Scope**: 1 migration new and 1 altered, 1 model, 3 new actions, 1 action and 1 enum changed, 3 controllers, 1 screen with 3 partials, 1 placeholder screen

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Every write is an action; the controller sequences three of them in a transaction (R4) |
| II. Simplicity | Pass. One table, one flag, two sync actions sharing one week shape; the editor is plain Alpine (R5) |
| III. Tested | Pass. Every new or changed action, the model, the view models and the three controllers get a test |
| IV. Laravel conventions | Pass. New migration with `up()` only; the pivot column goes into the existing migration (migrations skill) |
| V. Localized and safe | Pass. Every string through `__()` or `trans_choice()`; day names from Carbon (R5); nothing destructive without the owner's save |
| VI. Accessible | Pass, checked on desktop, tablet and phone with the keyboard (quickstart) |
| VII. One core | Justified gap: no API nor webhook exists yet (R9) |
| VIII. Short record | Pass. Every artifact under its cap |
| Invariant D2 | Pass with the reading of R1: weekly rules are wall clock times, as `staff_working_hours` already stores them |
| Invariant D10 | Pass. The availability engine is not built here; the tables it will read are queryable |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/013-onboarding-availability-step/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
├── contracts/routes.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
database/migrations/                             new: 2026_10_16_000000_create_location_opening_hours_table
                                                 altered: create_location_staff_table (follows_opening_hours)
database/factories/LocationOpeningHourFactory.php
app/Models/                                      LocationOpeningHour (new); Location gains openingHours();
                                                 Staff and Location pivots read follows_opening_hours
app/Actions/                                     new: SyncLocationOpeningHours, SyncStaffWorkingHours,
                                                 ApplyOpeningHoursToTeam
app/Enums/                                       OnboardingStepEnum (Availability), UserActionEnum (2 cases)
app/Http/Controllers/App/Onboarding/             AvailabilityController replaces AppointmentController
app/Http/Controllers/App/Onboarding/Availability/ StaffHoursController (edit, update)
app/Http/Controllers/App/DashboardController.php placeholder
app/ViewModels/Onboarding/                       AvailabilityViewModel extends StepViewModel; StaffHoursPanel
resources/views/app/onboarding/                  availability.blade.php replaces appointments.blade.php;
                                                 _opening-hours, _team-hours, _hours-panel; footer reused or copied
resources/views/app/dashboard.blade.php          placeholder
routes/web.php                                   routes of contracts/routes.md
lang/*.json                                      through jacques:localize
tests/Unit/{Actions,Models,ViewModels,Enums}/    mirroring the classes above
tests/Feature/Controllers/App/                   the three controllers, including another company's 404
```

**Structure Decision**: the existing Laravel layout; nothing new at the top of `app/`.

## Order of Work

1. Migration, column, `LocationOpeningHour`, factory, relations.
2. `SyncLocationOpeningHours`, `SyncStaffWorkingHours`, `ApplyOpeningHoursToTeam`, the two log cases.
3. Enum rename, routes, `DashboardController` and its placeholder; existing tests of step 4 renamed.
4. View models, `AvailabilityController`, then the screen: opening hours editor and footer (US1), team list and the box (US2), panel and `StaffHoursController` (US3), revisit (US4).
5. `jacques:localize`, Pint, PHPStan, quickstart.

## Complexity Tracking

No violation to justify.
