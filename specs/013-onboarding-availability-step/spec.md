# Feature Specification: Onboarding Step 4, Availability

**Feature Branch**: `2026-10-16-onboarding-availability-step` | **Created**: 2026-10-16 | **Status**: Draft | **Input**: "Add the last step of the onboarding", design `Onboarding Availability.dc.html`; "upon save, we go to the /dashboard page".

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The owner sets the salon's opening hours (Priority: P1)

Michael reaches "Availability" after step 3. The week is already filled with common hours. He
closes Dunder Mifflin Cuts on Monday, adds a lunch closure on Saturday, and chooses "Finish setup".
He lands on the dashboard.
**Why this priority**: appointments can only be booked when the salon is open; today step 4 is a placeholder.
**Independent Test**: open step 4 on a new salon, change two days, finish, check the dashboard opens and the hours are stored.
**Acceptance Scenarios**:

1. **Given** a salon with no opening hours, **When** step 4 opens, **Then** Monday to Wednesday read 9:00 to 18:00, Thursday and Friday 9:00 to 20:00, Saturday 9:00 to 17:00, Sunday is closed, and the total reads "6 days, 57 h a week".
2. **Given** a day, **When** its checkbox is unticked, **Then** the day reads "Closed", its hours count 0 h, and its "Edit hours" control is hidden.
3. **Given** an open day, **When** "Edit hours" or its timeline is chosen, **Then** the day expands with a start and an end picker per range, "Add opening hours", "Done", and "Close for lunch, 12:00 to 13:00" when one range covers 12:00 to 13:00.
4. **Given** "Close for lunch, 12:00 to 13:00" on Saturday, **When** chosen, **Then** Saturday holds 9:00 to 12:00 and 13:00 to 17:00, and its timeline shows two bars.
5. **Given** a range whose end is not after its start, or two ranges that overlap, **When** read, **Then** the day shows "The end has to come after the start." or "These hours overlap.", its timeline is outlined as an error, the footer says "Fix the opening hours marked in red to finish." and "Finish setup" is disabled.
6. **Given** valid hours, **When** "Finish setup" is chosen, **Then** the opening hours and the team's working hours are stored, the onboarding is marked as finished and the dashboard opens.

### User Story 2 - The team works the salon's hours (Priority: P1)

**Why this priority**: clients can only book somebody during their working hours; most salons start with everybody on the opening hours.
**Independent Test**: finish step 4 with "Use salon opening hours for all team members" ticked, check each bookable member works exactly the opening hours.
**Acceptance Scenarios**:

1. **Given** step 4, **When** it opens, **Then** "Use salon opening hours for all team members" is ticked, with its help text, and every bookable team member is listed with avatar initials, name, job title, "Salon opening hours" and a summary such as "6 days, 57 h a week".
2. **Given** the box ticked, **When** the opening hours change, **Then** every row's summary follows at once.
3. **Given** the box ticked, **When** "Finish setup" is chosen, **Then** every bookable team member works exactly the opening hours at the salon's first location.
4. **Given** the box ticked, **When** the footer is read, **Then** it says "3 team members on salon opening hours."

### User Story 3 - The owner gives somebody their own hours (Priority: P2)

Michael unticks the box and chooses "Customize" on Jim's row. A side panel opens on Jim's week,
filled from the opening hours. He gives Jim Tuesday to Friday with a lunch break and saves.
**Why this priority**: part time staff and lunch breaks are common, but not needed to take a first booking.
**Independent Test**: customize Jim, save, reload, check his row reads "Custom hours" and the panel shows his week.
**Acceptance Scenarios**:

1. **Given** the box unticked, **When** the list is read, **Then** each row offers "Customize", or "Edit hours" when the member already has custom hours, and the help says somebody without custom hours still follows the opening hours.
2. **Given** "Customize", **When** chosen, **Then** a side panel headed "Working hours" and the member's name lists the seven days, each with a checkbox, the salon's hours of that day, start and end pickers per range, "Add hours", the weekly total, "Start again from salon hours", "Cancel" and "Save hours".
3. **Given** a range outside the opening hours, **When** read, **Then** the day warns "Partly outside opening hours. Clients can only book while the salon is open.", or "The salon is closed on Sunday. Clients cannot book this day." on a closed day, and saving stays possible.
4. **Given** an invalid range in the panel, **When** read, **Then** the day shows the same error as the opening hours and "Save hours" is disabled.
5. **Given** "Save hours", **When** chosen, **Then** the member's working hours are stored at once, the panel closes, the row reads "Custom hours" with its own summary, and the footer says "3 team members, 1 with custom hours."
6. **Given** "Cancel", the close control, or Escape, **When** chosen, **Then** the panel closes and nothing is stored.
7. **Given** the box ticked again after customizing, **When** "Finish setup" is chosen, **Then** every bookable member works the opening hours, custom hours included.

### User Story 4 - The owner comes back to the step (Priority: P3)

**Why this priority**: the owner may leave with "Finish later" or go back to step 3 and return.
**Independent Test**: customize Jim, go back to step 3, return, check Jim's hours and the opening hours shown.
**Acceptance Scenarios**:

1. **Given** step 4, **When** "Your team" is chosen in the tracker, **Then** step 3 opens.
2. **Given** a member with custom hours stored, **When** step 4 opens again, **Then** the box is unticked and the member reads "Custom hours".
3. **Given** a finished onboarding, **When** any onboarding address is opened, **Then** the rules of spec 004 apply and the step is not shown again.

### Edge Cases

- A salon with every day closed has no bookable hour; "Finish setup" stays possible, and the footer says clients cannot book until a day is open.
- A team member added as not bookable in step 3 is not listed and receives no working hours.
- A team member made bookable later, outside onboarding, gets no hours from this step; Settings handles it (spec 009).
- Opening hours changed but not finished, then "Finish later": the changes are lost, as nothing but custom hours is stored before "Finish setup".
- A range ending at 22:00 is the latest possible; a range cannot cross midnight.
- Times are those of the location's time zone, set in step 1.
- Two owners finishing the step at the same time: the last one stored wins, and the onboarding is finished once.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Step 4 MUST be named "Availability" everywhere it appears, replace the placeholder "Appointments" step, and be reachable under the onboarding rules of spec 004.
- **FR-002**: The step MUST use the header shared by steps 1 to 3 (breadcrumb, eyebrow, "Finish later", tracker with steps 1 to 3 done), with the title "When can clients come in?" and the introduction of the design.
- **FR-003**: The opening hours MUST be one week, Monday first, each day open or closed, an open day holding one or more ranges, each with a start and an end chosen every 30 minutes between 6:00 and 22:00.
- **FR-004**: Each day MUST show its short name, its total hours, and a timeline from 6:00 to 22:00 with one bar per range, the times written inside a bar when it is wide enough and shortened otherwise.
- **FR-005**: A salon with no opening hours stored MUST be offered the default week of US1 scenario 1; a salon with hours stored MUST be shown its own.
- **FR-006**: A day MUST be refused when a range ends at or before its start, or when two ranges overlap, with the messages of US1 scenario 5.
- **FR-007**: The section "Working hours of the team" MUST list every active, bookable team member of the salon's first location, owner first, with avatar initials, name, job title, mode and weekly summary.
- **FR-008**: "Use salon opening hours for all team members" MUST be ticked by default, and ticked again when no member has custom hours stored.
- **FR-009**: Custom hours MUST be edited in a side panel per member, stored at once when saved, and kept apart from the opening hours.
- **FR-010**: Hours outside the opening hours MUST be warned about in the panel and MUST NOT be refused.
- **FR-011**: The footer MUST show the status text of US1 to US3 and "Finish setup", disabled while an opening day is in error, and sit in the page flow under a rule, as in steps 2 and 3, not fixed to the bottom of the window.
- **FR-012**: "Finish setup" MUST, in one operation, store the opening hours of the salon's first location, replace the working hours of every listed member who follows the opening hours with a copy of them, keep the custom hours of the others unless the box is ticked, complete step 4, and mark the onboarding as finished.
- **FR-013**: "Finish setup" MUST lead to the `/dashboard` address, and that address MUST exist for a signed in user.
- **FR-014**: Every change MUST be logged, counts MUST use plural rules, every text MUST be translated, and the step MUST meet WCAG 2.2 AA, the panel trapping focus and returning it on close, and errors given in text, not colour alone.

### Key Entities *(include if feature involves data)*

- **Location opening hour**: one weekly range during which a location is open, with its day of the week, start and end, in the location's time zone. New.
- **Staff working hour**: one weekly range a team member works at a location (spec 009). Exists.
- **Team member**: the staff record of spec 009, bookable or not.
- **Onboarding step**: the fourth step becomes "Availability".

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An owner whose week matches the defaults finishes the onboarding in one interaction.
- **SC-002**: An owner adds a lunch closure to a day in two interactions.
- **SC-003**: After "Finish setup", every bookable team member has working hours, and none falls on a day the owner left closed unless set in their custom hours.
- **SC-004**: No saved custom hours are lost when the owner reloads, leaves, or returns from step 3.
- **SC-005**: An owner reaches the dashboard right after finishing, without an intermediate screen.

## Assumptions

- The design's "Your salon is ready!" recap is not built: "Finish setup" leads straight to the dashboard (user request).
- No dashboard exists yet. The `/dashboard` address is added with a minimal placeholder screen; designing it is a later feature. The home address `/` is unchanged.
- The booking rules defined in the design's script but not shown (interval, notice, advance) are out of scope.
- The opening hours belong to the salon's first location, the one set up in step 1; other locations are handled in Settings.
- "Their hours follow if you change the opening hours" holds within this step: following members receive a copy of the opening hours when the step is finished. Keeping them in step after the onboarding is a later feature.
- Whether a member has custom hours is remembered by the system, not guessed from comparing their hours with the salon's.
- Holidays, one-off closures and absences are added later from Settings, as the design says.
- The design's sample salon and team stand for the salon's real data.
- The design's "Back to your team" link and sticky footer are not followed: the header and footer repeat steps 2 and 3, as the owner asked for step 3 (spec 012).
