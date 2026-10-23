# Tasks: Dashboard agenda

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md`; signed in people come from The Office, generated hairdressers keep the design's names.

## Phase 1: Foundational

- [X] T001 Create app/ViewModels/Dashboard/GeneratedAgenda.php: `hairdressers()` (amelie 310, sammy 250, charlotte 200, alexandra 80 not visible by default, lucas 30), `services()` (eight `__()` names with durations 45, 30, 30, 90, 120, 20, 20, 30), `clients()` (the design's fifteen names), `plan(string $hairdresserId, CarbonImmutable $date): ?array` returning null on Sunday, seeded with `new Randomizer(new Mt19937(crc32("{$id}{$date->format('Y-m-d')}")))` and the design's rules (research R2, data-model rules)
- [X] T002 [P] Create tests/Unit/ViewModels/Dashboard/GeneratedAgendaTest.php: same plan twice for one day, null on a Sunday, no appointment for an absent hairdresser, every appointment inside working hours and outside lunch, five hairdressers with alexandra hidden by default

## Phase 2: User Story 1, read the day of the salon (P1)

Independent test: Michael opens the dashboard and reads today's columns, blocks, counts and time line.

- [X] T003 [US1] Create app/ViewModels/Dashboard/DayAgenda.php from a date and `GeneratedAgenda`: `hours()` 9:00 to 19:00 labelled with `isoFormat('LT')`, `height()` 900, `columns()` per data-model.md with `countLabel` via `trans_choice(':count appointment|:count appointments', $n)` or `__('Absent all day')`, `dayItems` (`__(':start to :end')`, `__('Lunch :time')` or `__('On leave')`), `offBlocks` labelled `__('Not arrived yet')`, `__('Lunch break')`, `__('Gone')`, `__('On leave')`, appointments with `top = (start - 540) * 1.5 + 1`, `height = duration * 1.5 - 2`, `compact` under 44, and `workingRanges`
- [X] T004 [US1] Create app/ViewModels/Dashboard/DashboardViewModel.php taking `CarbonImmutable $date` and `CarbonImmutable $now` (salon time zone): `title()` via `isoFormat('dddd D MMMM YYYY')`, `isClosed()`, `day()`, `hairdressers()`, `services()`, `isToday()`, `nowTop()`, `nowLabel()`, `previousDayUrl()`, `nextDayUrl()`, `todayUrl()`, `alpineState()` (visible ids, slot 20, services, columns' working ranges)
- [X] T005 [P] [US1] Create tests/Unit/ViewModels/Dashboard/DayAgendaTest.php: hour labels, off blocks for a late start and for leave, block geometry and `compact`, count label at one and at several
- [X] T006 [P] [US1] Create tests/Unit/ViewModels/Dashboard/DashboardViewModelTest.php: title, closed on Sunday, now line only on today within 9:00 to 19:00, day URLs
- [X] T007 [US1] In routes/web.php add `Route::get('dashboard/{date}', [DashboardController::class, 'show'])->where('date', '\d{4}-\d{2}-\d{2}')->name('dashboard.show')` beside `dashboard.index` (contracts/routes.md)
- [X] T008 [US1] In app/Http/Controllers/App/DashboardController.php resolve the time zone from the company's first location, else `config('app.timezone')` (research R6); `index` uses today, `show` parses `date` with `CarbonImmutable::createFromFormat('!Y-m-d')` and aborts 404 when it does not round trip; both return `app.dashboard` with the view model
- [X] T009 [US1] Rewrite resources/views/app/dashboard.blade.php: keep the welcome message, two column layout (`lg:flex`, left column `lg:w-68`, stacked under `lg`), an `x-data` holding `alpineState()`; include the day partial
- [X] T010 [US1] Create resources/views/app/dashboard/_day.blade.php: header (Today, previous and next as `x-button.secondary` links with `aria-label`, title, summary), Sunday message `__('The salon is closed that day')`, sticky column headers with `x-avatar-initials tone="accent"` and `style="--tile-hue: {hue}"`, Day row, hour scale, hatched off blocks with their label, appointment blocks as `<button>` tinted `oklch(0.95 0.035 hue)` (dark variant per research R7), the red now line moved every 30 seconds by Alpine; grid scrolls in its own area
- [X] T011 [US1] Extend tests/Feature/Controllers/App/DashboardControllerTest.php: Michael sees today's title, `dashboard/2026-10-26` shows that Monday, a Sunday shows the closed message, `dashboard/2026-02-30` is not found, the welcome message still shows after onboarding

## Phase 3: User Story 2, move through days and choose who to see (P2)

Independent test: go to tomorrow, page the calendar two months, pick a day, uncheck a hairdresser, change the slot size.

- [X] T012 [US2] Create app/ViewModels/Dashboard/MonthCalendar.php from a selected date, a month and today: `label()` via `isoFormat('MMMM YYYY')`, `weekdayInitials()` Monday first, `weeks()` per data-model.md dropping a sixth row outside the month, `previousUrl()` and `nextUrl()` to `dashboard.calendar.show`; expose it from `DashboardViewModel::calendar()`
- [X] T013 [P] [US2] Create tests/Unit/ViewModels/Dashboard/MonthCalendarTest.php: week starts Monday, selected and today flags, Sunday flagged, month URLs
- [X] T014 [US2] In routes/web.php add `dashboard/{date}/calendar/{month}` named `dashboard.calendar.show` with `where` patterns; create app/Http/Controllers/App/DashboardCalendarController.php `show` that 404s on an invalid date or month and returns the full dashboard with the calendar on that month (contracts/routes.md)
- [X] T015 [P] [US2] Create tests/Feature/Controllers/App/DashboardCalendarControllerTest.php: shows the requested month with the date selected, 404 on `2026-13`
- [X] T016 [US2] Create resources/views/app/dashboard/_calendar.blade.php with `id="calendar"`: month arrows as links with `x-target="calendar"` and `aria-label`, day links to `dashboard.show`, selected filled with `bg-primary`, today outlined, Sundays and other months `text-muted-soft`, `aria-current="date"` on the selected day
- [X] T017 [US2] Create resources/views/app/dashboard/_hairdressers.blade.php: "Book an appointment" button, heading `__('Hairdressers')`, hint, "All hairdressers" checkbox and one per hairdresser bound to `visible`; in _day.blade.php hide unchecked columns with `x-show`, sum the visible counts into the summary, show `__('No hairdresser is shown')` when none, and a slot size `<select>` (15, 20, 30) driving the faint line spacing through a CSS variable
- [ ] T018 [US2] Create tests/Browser/DashboardTest.php: next day arrow changes the URL, calendar paging keeps the day, unchecking all shows the empty message, checking All shows five columns

## Phase 4: User Story 3, open, book and cancel in the mock (P3)

Independent test: open a block, cancel it after confirming, book a free slot, reload and see the generated day again.

- [X] T019 [US3] In _day.blade.php render free slots per column as transparent `<button>`s built with `x-for` from `workingRanges` and `slot`, each with `aria-label` `__('Book at :time with :name')`; render `added` blocks and hide `removed` ones (research R4, R5)
- [X] T020 [US3] Create resources/views/app/dashboard/_appointment-panel.blade.php in `x-side-modal`: view mode with client, service and duration, hairdresser, time, and an inline confirmation (`__('Cancel this appointment')`, `__('Cancel it')`, `__('Keep it')`); booking mode with title, `x-input` client name, service `<select>`, add disabled while the name is blank, and Close
- [ ] T021 [US3] Extend tests/Browser/DashboardTest.php: Keep it keeps the block, Cancel it removes it, an empty name disables Add, adding draws the block, a reload restores the generated day

## Phase 5: Polish

- [X] T022 Run `php artisan jacques:localize`, translate every reported string in each lang/*.json locale using the design's French copy for fr_FR, then `php artisan jacques:localize --check`
- [X] T023 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [ ] T024 Run `php artisan test --compact` and the manual scenarios of quickstart.md at 375, 768 and 1280 px in light and dark mode

## Dependencies

- T001 blocks every story. US2 and US3 need the view and Alpine component of T009 and T010.
- T010, T017 and T019 all edit _day.blade.php: do them in order. T007 and T014 both edit routes/web.php.
- Parallel examples: T002 with T003; T005 with T006; T013 with T015; T022 with T023.

## Implementation Strategy

MVP is Phases 1 and 2: a read only agenda of one day per URL. US2 adds navigation and filters, US3 the mock panels; all three ship in one pull request.
