# Research: Onboarding Step 4, Availability

## R1. Opening hours are their own table

- Decision: `location_opening_hours`, one row per range, shaped like `staff_working_hours`: day of the week from 0 for Sunday to 6 for Saturday, start and end as `H:i:s` in the location's time zone. A closed day has no row.
- Weekly wall clock times are not converted to UTC, as `StaffWorkingHour` already decides: a salon opens at 9:00 all year round, across daylight saving changes (D2 applies to moments, not to weekly rules).
- `SyncLocationOpeningHours` (new) replaces the whole week of a location in one call and logs once (`LocationOpeningHoursUpdated`). It checks `LocationManage`.
- Rejected: a JSON column on `locations` (cannot be queried by the future availability engine); one action per range, as Settings does for staff (one log line per checkbox, and the step saves a week at once).

## R2. A member's week is replaced in one call

- Decision: `SyncStaffWorkingHours` (new) takes a staff member, a location, a week and whether the member follows the opening hours. It deletes the member's ranges at that location, writes the new ones, sets the flag of R3, and logs once (`StaffWorkingHoursUpdated`). It checks `ScheduleManage` for that staff member and the assignment, as `CreateStaffWorkingHour` does.
- The week shape is shared by both sync actions: `array<int, list<array{start: string, end: string}>>`, keyed by day of the week, times written `HH:MM`.
- Both actions refuse, with `InvalidArgumentException`, a time not written `HH:MM`, a range that does not end after it starts, and two ranges of a day that overlap. The 30 minute grid between 6:00 and 22:00 is a rule of the form, not of the actions, so Settings may later offer finer times.
- Rejected: calling `CreateStaffWorkingHour` per range (one log line per range, and overlap checks against rows about to be deleted).

## R3. "Custom hours" is a flag on the assignment

- Decision: `location_staff.follows_opening_hours`, boolean, true by default, added to the existing migration (migrations skill, pre production). Hours belong to a location, so the flag does too.
- A saved custom panel sets it to false. "Finish setup" with the box ticked sets it to true for every listed member.
- The Settings actions on single ranges leave it untouched; keeping followers in step with later changes is a later feature (spec assumption).
- Rejected: comparing a member's hours with the salon's on each read (a member who customized then matched the salon would silently become a follower).

## R4. "Finish setup" is three actions in a transaction

- Decision: `PUT onboarding/availability` runs `SyncLocationOpeningHours`, then `ApplyOpeningHoursToTeam` (new), then `CompleteOnboardingStep`, and redirects to `dashboard.index`.
- `ApplyOpeningHoursToTeam` takes the location and whether custom hours are overridden. It runs `SyncStaffWorkingHours` with the opening week for every active, bookable member actively assigned there who follows the opening hours, or for all of them when overridden.
- A refusal from an action (R2) is turned into `back()->withErrors(['days' => ...])`; the screen already disables the button on the same conditions, so this only guards a forged request.

## R5. The editor lives in the browser until "Finish setup"

- The week, its totals, bars, errors, the summaries of following members and the footer sentence all react to each change before anything is stored (spec US1, US2).
- Decision: one Alpine component wraps the step, seeded from the view model as JSON (stored week, or the default week of spec FR-005). Hidden inputs generated with `x-for` carry the week in the form. Translated sentences are passed in from the view model. A count that changes in the browser is the number of open days, 0 to 7, so the view model passes `trans_choice()` rendered for each of the eight values, with `:hours` left for the browser to fill.
- Day names come from Carbon's `translatedFormat('D')`, so no new translation key per day. Times read `9:00`, as drawn.
- Rejected: a request per change (the design has a single "Finish setup", and a half edited week is not worth storing).

## R6. The custom hours panel opens through its own address

- Decision: as in steps 2 and 3, `GET onboarding/availability/staff/{staff}/edit` renders the step with the panel open, and Alpine Ajax swaps only `hours-panel`. Saving swaps `team-hours`, `hours-panel` and `availability-footer`, leaving the opening hours section and its unsaved state untouched.
- The panel reads the salon's hours of each day, the warnings and "Start again from salon hours" from the parent component's current week, not from the database, so they match what the owner sees.
- A member not active, not bookable, or not assigned to the headquarters is a 404, as is a member of another company.

## R7. Header and footer follow steps 2 and 3

- The shared `_header` partial (breadcrumb, eyebrow "Onboarding · Step 4 of 4", "Finish later", tracker) is kept instead of the design's "Back to your team" link, so the four steps stay identical; step 3 is reached from the tracker.
- The footer sits in the page flow under a rule, as the owner asked for step 3 (spec 012, assumption), not sticky as drawn. Spec FR-002 and FR-011 are amended accordingly.

## R8. The step is renamed, and a dashboard address appears

- `OnboardingStepEnum::Appointments` becomes `Availability`, its route `onboarding.availability.index` at `onboarding/availability`. `AppointmentController` and `appointments.blade.php` are replaced, not kept beside.
- `GET dashboard`, named `dashboard.index`, behind `auth`, `set.locale` and `welcome.screen`, renders a placeholder under the top bar. `/` and the redirects of `OnboardingStepGate` are unchanged (spec 004).

## R9. API and webhooks

- No API and no webhook exist yet (constitution VII); the pull request says so, as specs 008 to 012 did.
