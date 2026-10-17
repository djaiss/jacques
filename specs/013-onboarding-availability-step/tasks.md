# Tasks: Onboarding Step 4, Availability

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Create database/migrations/2026_10_16_000000_create_location_opening_hours_table.php per data-model.md: `location_id`, `day_of_week` "from 0 for Sunday to 6 for Saturday", `start_time`, `end_time` "in the time zone of the location", timestamps, each with `->comment()`, only `up()`, foreign key to `locations` cascading on delete declared at the bottom, index `(location_id, day_of_week)`
- [X] T002 [P] Add `follows_opening_hours` "boolean, default true" with its comment to database/migrations/2026_08_01_000005_create_location_staff_table.php (R3, edit the existing migration per the migrations skill)

## Phase 2: Foundational (records, sync actions, rename, dashboard)

- [X] T003 Create app/Models/LocationOpeningHour.php (`#[Fillable]`, `day_of_week` cast to integer, `location()` belongs to, full model docblock modelled on app/Models/StaffWorkingHour.php) and database/factories/LocationOpeningHourFactory.php; add `openingHours()` has many to app/Models/Location.php; add `follows_opening_hours` to `withPivot` in app/Models/Staff.php `locations()` and app/Models/Location.php `staff()`
- [X] T004 [P] Write tests/Unit/Models/LocationOpeningHourTest.php (relationship exists) and add the `openingHours()` case to tests/Unit/Models/LocationTest.php
- [X] T005 Create app/Actions/SyncLocationOpeningHours.php (author, location, week `array<int, list<array{start: string, end: string}>>` keyed 0 to 6; `LocationManage`; location of the author's company else ModelNotFoundException; refuses with InvalidArgumentException a time not `HH:MM`, a day outside 0 to 6, a range not ending after it starts, two overlapping ranges of a day; replaces every row of the location; logs once) and add `LocationOpeningHoursUpdated` "Changed the opening hours of the location called :location" to app/Enums/UserActionEnum.php, tested in tests/Unit/Actions/SyncLocationOpeningHoursTest.php (Scranton's week replaced, a closed day has no row, each refusal, another company's location)
- [X] T006 Create app/Actions/SyncStaffWorkingHours.php (author, staff, location, the same week shape, `followsOpeningHours` bool; `ScheduleManage` for the staff member; staff assigned to the location else InvalidArgumentException; the same time checks as T005; deletes the member's ranges at that location, writes the new ones, sets the pivot flag; logs once) and add `StaffWorkingHoursUpdated` "Changed the working hours of :name at the location called :location" to app/Enums/UserActionEnum.php, tested in tests/Unit/Actions/SyncStaffWorkingHoursTest.php (Jim's week replaced and flag false, an unassigned member refused, other locations untouched)
- [X] T007 Create app/Actions/ApplyOpeningHoursToTeam.php (author, location, `overrideCustomHours` bool; runs SyncStaffWorkingHours with the location's stored opening week and `followsOpeningHours: true` for every active, bookable member actively assigned there who follows the opening hours, or all of them when overridden), tested in tests/Unit/Actions/ApplyOpeningHoursToTeamTest.php (Michael receives the week, Jim's custom hours kept then overridden, Pam not bookable untouched)
- [X] T008 Rename `OnboardingStepEnum::Appointments` to `Availability` in app/Enums/OnboardingStepEnum.php (label "Availability", route `onboarding.availability.index`) and every use: app/ViewModels/Onboarding/StepViewModel.php, app/Http/Controllers/App/Onboarding/StaffController.php (redirect), "Continue to availability" in resources/views/app/onboarding/_team-footer.blade.php, and tests/Unit/Enums/OnboardingStepEnumTest.php, tests/Unit/ViewModels/Onboarding/StepViewModelTest.php, tests/Unit/Actions/CompleteOnboardingStepTest.php, tests/Feature/Controllers/App/Onboarding/OnboardingControllerTest.php and StaffControllerTest.php
- [X] T009 Replace app/Http/Controllers/App/Onboarding/AppointmentController.php with AvailabilityController.php (`index` with a temporary StepViewModel, `update` completing the step), replace resources/views/app/onboarding/appointments.blade.php with availability.blade.php, swap the `onboarding.appointments.*` routes for `onboarding.availability.index` and `update` in routes/web.php, and move tests/Feature/Controllers/App/Onboarding/AppointmentControllerTest.php to AvailabilityControllerTest.php
- [X] T010 [P] Create app/Http/Controllers/App/DashboardController.php `index`, resources/views/app/dashboard.blade.php (placeholder under `x-top-bar-layout`, Blade header comment), and `GET dashboard` named `dashboard.index` in its own group with `auth`, `set.locale`, `welcome.screen` in routes/web.php; test in tests/Feature/Controllers/App/DashboardControllerTest.php (renders for Michael, a guest is sent to sign in)
- [X] T011 Run `php artisan test --compact`, which must be green

## Phase 3: User Story 1, the owner sets the salon's opening hours (P1) MVP

Independent test: open step 4 on a new salon, change two days, finish, check the dashboard opens and the hours are stored.

- [X] T012 [US1] Create app/ViewModels/Onboarding/AvailabilityViewModel.php extending StepViewModel: the headquarters, its opening week from the database or the default week of data-model.md, Monday first, day names through Carbon `translatedFormat('D')`, time options every 30 minutes from 06:00 to 22:00 labelled `9:00`, title "When can clients come in?", the design's introduction, the summary phrases for 0 to 7 open days through `trans_choice()` with `:hours` left to the browser (R5), the error and footer sentences; tested in tests/Unit/ViewModels/Onboarding/AvailabilityViewModelTest.php (default week, stored week, Sunday stored as 0 shown last)
- [X] T013 [US1] Make `update` of app/Http/Controllers/App/Onboarding/AvailabilityController.php validate the week form exactly as contracts/routes.md lists it, then in one transaction run SyncLocationOpeningHours, ApplyOpeningHoursToTeam with `overrideCustomHours` from `use_opening_hours`, and CompleteOnboardingStep, and redirect to `dashboard.index`; an InvalidArgumentException returns `back()->withErrors(['days' => ...])`
- [X] T014 [US1] Create resources/views/app/onboarding/_opening-hours.blade.php and rewrite availability.blade.php from the shared `_header` partial: one Alpine component seeded with the view model's JSON, per day the checkbox with the short name, total hours, timeline 6:00 to 22:00 with ticks and one bar per range (times inside when 5 h or more, shortened otherwise), "Closed", "Edit hours" or "Close", the expanded editor with start and end selects, remove control, "Add opening hours", "Close for lunch, 12:00 to 13:00" when one range covers 12:00 to 13:00, "Done", the errors "The end has to come after the start." and "These hours overlap." in text, and hidden inputs carrying `days[i][open]` and `days[i][ranges][k][start|end]`
- [X] T015 [US1] Create resources/views/app/onboarding/_availability-footer.blade.php copying the layout of _team-footer.blade.php (in the page flow under a top rule, not sticky, spec FR-011) with the status sentence and "Finish setup", disabled with "Fix the opening hours marked in red to finish." while a day is in error, and the closed week sentence of the edge cases
- [X] T016 [US1] Extend tests/Feature/Controllers/App/Onboarding/AvailabilityControllerTest.php: index renders the default week, update stores Scranton's week with Saturday's lunch closure and Monday closed, ends the onboarding and redirects to the dashboard, a range ending before it starts and a time off the grid are refused, a non owner and a company on step 3 are redirected

## Phase 4: User Story 2, the team works the salon's hours (P1)

Independent test: finish with the box ticked, check each bookable member works exactly the opening hours.

- [X] T017 [US2] Add to AvailabilityViewModel the active, bookable members actively assigned to the headquarters, owner first then position, with avatar hue, name, job title, whether they follow the opening hours, their own week summary, `isUsingOpeningHours()` true when nobody has custom hours (FR-008), and the footer sentences ":count team member on salon opening hours.|:count team members on salon opening hours." and ":count team member, :custom with custom hours.|:count team members, :custom with custom hours." through `trans_choice()`
- [X] T018 [US2] Create resources/views/app/onboarding/_team-hours.blade.php (fragment `team-hours`): the "Use salon opening hours for all team members" checkbox bound to the Alpine component and submitted as `use_opening_hours`, its two help texts, and one row per member with `x-avatar-initials`, name, job title, mode, and a summary that follows the editor's week live for followers
- [X] T019 [US2] Extend tests: Michael and Jim listed and Pam not in tests/Unit/ViewModels/Onboarding/AvailabilityViewModelTest.php; finishing with the box ticked gives both the opening week and sets the flag in tests/Feature/Controllers/App/Onboarding/AvailabilityControllerTest.php

## Phase 5: User Story 3, the owner gives somebody their own hours (P2)

Independent test: customize Jim, save, reload, check his row reads "Custom hours" and the panel shows his week.

- [X] T020 [US3] Add `onboarding.availability.staff.edit` and `update` to routes/web.php per contracts/routes.md, with `onboarding.step:4` and `whereNumber('staff')`
- [X] T021 [US3] Create app/ViewModels/Onboarding/StaffHoursPanel.php (member name, their week at the headquarters or the opening week when they follow it, form action) returned by AvailabilityViewModel when `openStaff` is set
- [X] T022 [US3] Create app/Http/Controllers/App/Onboarding/Availability/StaffHoursController.php: `edit` renders the step with the panel open, `update` validates the week form and runs SyncStaffWorkingHours with `followsOpeningHours: false`, then redirects to the index; a member of another company, inactive, not bookable or not assigned to the headquarters is a 404
- [X] T023 [US3] Create resources/views/app/onboarding/_hours-panel.blade.php inside `x-side-modal` (fragments `hours-panel-{id}` and `hours-panel-closed`, R6): eyebrow "Working hours", the member's name, weekly total, "Start again from salon hours", seven days with checkbox, the salon's hours of that day read from the parent week, selects, remove control, "Add hours", the errors, the warnings "Partly outside opening hours. Clients can only book while the salon is open." and "The salon is closed on :day. Clients cannot book this day.", "Cancel" and "Save hours" disabled on error; add "Customize" or "Edit hours" to each row of _team-hours.blade.php, shown while the box is unticked
- [X] T024 [US3] Write tests/Feature/Controllers/App/Onboarding/Availability/StaffHoursControllerTest.php: edit renders Jim's panel, update stores Jim's Tuesday to Friday with a lunch break and a Sunday range, Pam not bookable and Creed of another company return 404, an overlap is refused

## Phase 6: User Story 4, the owner comes back to the step (P3)

Independent test: customize Jim, go back to step 3, return, check Jim's hours and the opening hours shown.

- [X] T025 [US4] Cover revisits in tests/Feature/Controllers/App/Onboarding/AvailabilityControllerTest.php: with Jim's custom hours stored the box is unticked and his row reads "Custom hours"; with stored opening hours the stored week is shown; an onboarded company is redirected home

## Phase 7: Polish

- [X] T026 Run `php artisan jacques:localize` and translate every reported string in each lang/*.json locale, then `php artisan jacques:localize --check`
- [X] T027 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [X] T028 Run every check and the manual walk of quickstart.md, at desktop, tablet and phone widths, keyboard only

## Dependencies

- T001 and T002 before Phase 2. Phase 2 blocks every story. US1 provides the screen and controller that US2 and US3 extend.
- US2 needs US1's Alpine component; US3 needs US2's rows. US4 needs US3. They share AvailabilityViewModel and availability.blade.php, so run them in order.
- Parallel examples: T002 with T001; T004 with T005; T005 with T010 (T005 and T006 both edit UserActionEnum); T027 alongside T026.

## Implementation Strategy

MVP is Phases 1 to 3: the opening hours table, the sync actions, the rename, the dashboard, then the opening hours editor ending the onboarding. Each later phase ships on its own with the suite green.
