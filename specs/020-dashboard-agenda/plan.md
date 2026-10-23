# Implementation Plan: Dashboard agenda

**Branch**: `2026-10-23-dashboard-agenda` | **Date**: 2026-10-23 | **Spec**: [spec.md](spec.md)

## Summary

The dashboard placeholder becomes the day agenda of `Agenda Salon.dc.html`. The server renders one
day per URL (`dashboard` for today, `dashboard/{date}` for any other) from a view model fed by a
seeded generator, so a day always shows the same content. The month calendar pages through months
with Alpine Ajax. Hairdresser checkboxes, slot size, the side panel, bookings and cancellations live
in Alpine state only and are lost on reload. Decisions: [research.md](research.md). Shapes:
[data-model.md](data-model.md). Routes: [contracts/routes.md](contracts/routes.md).

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Blade, Tailwind, Alpine.js, Alpine Ajax, `Random\Randomizer`
- **Storage**: none read or written for the agenda; the salon time zone is read from its location
- **Testing**: Pest unit tests on the view model blocks, feature tests on both controllers, one browser test
- **Target Platform**: web back office, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: no query beyond the layout and the time zone lookup
- **Constraints**: light and dark mode, WCAG 2.2 AA, 375 pixel wide screen, locale aware dates and counts
- **Scale/Scope**: 2 controllers, 4 view model classes, 1 view and 4 partials, 2 routes, translations, 5 test files

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Nothing is written, so no action; the generator is presentation data in `app/ViewModels` (R1) |
| II. Simplicity | Pass. One generator, one block per region of the screen; client state stays in one Alpine component |
| III. Tested | Pass. Every new class and controller has a test; determinism and Sunday covered |
| IV. Laravel conventions | Pass. Resourceful `index` and `show`, a separate controller for the calendar, no query string (R3) |
| V. Localized and safe | Pass. `__()`, `trans_choice()`, `isoFormat()`; cancelling asks first (FR-007) |
| VI. Accessible | Pass. Free slots are real buttons (R5), the panel reuses `x-side-modal`, hatching is labelled in text |
| VII. One core | Not applicable: a mock with no data |
| VIII. Short record | Pass. Every artifact under its cap |
| Invariant D2 (UTC, salon time zone) | Pass. "Today" and the current time line use the location's time zone (R6) |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/020-dashboard-agenda/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
├── contracts/routes.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
routes/web.php                                              dashboard.show, dashboard.calendar.show
app/Http/Controllers/App/DashboardController.php            index (today) and show (a date)
app/Http/Controllers/App/DashboardCalendarController.php    show: the calendar partial for a month
app/ViewModels/Dashboard/DashboardViewModel.php             title, summary, blocks, Alpine payload
app/ViewModels/Dashboard/GeneratedAgenda.php                seeded hairdressers, hours and appointments (R1, R2)
app/ViewModels/Dashboard/DayAgenda.php                      columns, blocks, hatching, hour scale (R4)
app/ViewModels/Dashboard/MonthCalendar.php                  weeks from Monday, selected and today flags
resources/views/app/dashboard.blade.php                     layout, welcome message, Alpine component
resources/js/day-agenda.js                                  Alpine state: checkboxes, slots, panel, time line
resources/css/app.css                                       appointment tint, hour lines, hatching
app/View/Components/TopBarLayout.php                        `wide` option, the agenda takes the full width
resources/views/app/dashboard/_calendar.blade.php           month grid, target of Alpine Ajax
resources/views/app/dashboard/_hairdressers.blade.php       checkboxes
resources/views/app/dashboard/_day.blade.php                header, empty states, grid
resources/views/app/dashboard/_appointment-panel.blade.php  view, cancel, book (x-side-modal)
lang/*.json                                                 through jacques:localize
tests/Unit/ViewModels/Dashboard/*Test.php                   GeneratedAgenda, DayAgenda, MonthCalendar, DashboardViewModel
tests/Feature/Controllers/App/DashboardControllerTest.php   today, a date, Sunday, invalid date, welcome
tests/Feature/Controllers/App/DashboardCalendarControllerTest.php
tests/Browser/DashboardTest.php                             toggles, slot size, cancel, book
```

**Structure Decision**: the existing `App` controllers and a `Dashboard` view model folder, the
complex page layout the view models skill prescribes.

## Order of Work

1. `GeneratedAgenda` and its test (FR-002, FR-003).
2. `DayAgenda`, `MonthCalendar`, `DashboardViewModel` and their tests (US1).
3. Routes, both controllers, feature tests (US1, US2).
4. Views: day grid and hatching (US1), calendar and checkboxes (US2), panel (US3).
5. Browser test; `jacques:localize`; Pint, PHPStan; quickstart at three widths in both themes.

## Complexity Tracking

No violation to justify.
