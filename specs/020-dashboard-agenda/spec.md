# Feature Specification: Dashboard agenda

**Feature Branch**: `2026-10-23-dashboard-agenda` | **Created**: 2026-10-23 | **Status**: Draft

**Input**: User description: "add default view of the dashboard of the app", with the design `Agenda Salon.dc.html` (claude.ai/design project 0592e1b2). "make it fake for now."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Somebody reads the day of the salon (Priority: P1)

The dashboard replaces its placeholder with a day agenda. A left column holds a "Book an appointment" button, a month calendar and the list of hairdressers with checkboxes. The main area shows the chosen day: a header with Today, previous day and next day buttons, the day as a title with the total count of appointments under it, and a slot size selector. Below, one column per checked hairdresser, from 9:00 to 19:00, with an hour scale on the left. Each column header shows the avatar, the name and the count of appointments of that person. A "Day" row under the headers shows the working hours and the lunch break, or "On leave". Appointments are blocks showing time range, client and service. Time before arrival, the lunch break, time after departure and a full day of leave are hatched and labelled.

**Why this priority**: the agenda is what a salon looks at all day. It is the screen the app opens on.

**Independent Test**: sign in, open the dashboard, and read today's columns, blocks and counts.

**Acceptance Scenarios**:

1. **Given** a signed in person, **When** they open the dashboard, **Then** today is selected in the calendar and shown in the main area.
2. **Given** today is shown, **When** the current time is between 9:00 and 19:00, **Then** a red line labelled with the time crosses the columns at that time.
3. **Given** a hairdresser on leave that day, **When** the day is shown, **Then** their column is fully hatched with "On leave" and its header reads "Absent all day".
4. **Given** the same day is opened twice, **When** the person compares both, **Then** the same appointments are shown.

---

### User Story 2 - Somebody moves through days and chooses who to see (Priority: P2)

The person changes the shown day with the calendar, the Today button or the arrows, moves the calendar month by month, and checks or unchecks hairdressers, one by one or all at once.

**Why this priority**: reading only today has limited use.

**Independent Test**: go to tomorrow, then to a date next month, uncheck a hairdresser, and check the grid follows each step.

**Acceptance Scenarios**:

1. **Given** the agenda, **When** the person picks a date in the calendar, **Then** the main area shows that date and the calendar marks it, while today keeps an outline.
2. **Given** the agenda, **When** the person presses the next or previous day arrow, **Then** the shown day moves by one and the calendar follows to its month.
3. **Given** a Sunday is picked, **When** the main area shows, **Then** it reads "The salon is closed that day" with an explanation, and no grid.
4. **Given** every hairdresser is unchecked, **When** the main area shows, **Then** it reads "No hairdresser is shown" and asks to check at least one.
5. **Given** "All hairdressers" is checked, **When** the person unchecks one, **Then** "All hairdressers" becomes unchecked.
6. **Given** the slot size is changed to 15, 20 or 30 minutes, **When** the grid redraws, **Then** its faint lines follow that size.

---

### User Story 3 - Somebody opens, books and cancels appointments in the mock (Priority: P3)

Pressing an appointment opens a side panel with the client, the service and duration, the hairdresser and the time. "Cancel this appointment" asks for a confirmation before removing it. Pressing a free slot in a column, or the "Book an appointment" button, opens a panel titled with the time and the hairdresser, asking for a client name and a service; the duration follows the service.

**Why this priority**: shows the intended flows, but nothing is saved in this version.

**Independent Test**: open an appointment, cancel it after confirming, then book one on a free slot and see it in the grid.

**Acceptance Scenarios**:

1. **Given** an appointment, **When** the person presses it, **Then** the side panel shows its details.
2. **Given** the panel, **When** the person asks to cancel, **Then** a warning with "Cancel it" and "Keep it" shows, and only "Cancel it" removes the block.
3. **Given** a free slot, **When** the person presses it, **Then** the booking panel opens at the start of that slot.
4. **Given** the booking panel, **When** the client name is empty, **Then** "Add the appointment" cannot be pressed.
5. **Given** a booking or a cancellation, **When** the page reloads, **Then** the agenda is back to its generated content.

---

### Edge Cases

- Pressing hatched time (before arrival, lunch, after departure, leave) opens nothing.
- A short appointment shows time, client and service on one line instead of three.
- Many checked hairdressers make the grid scroll sideways inside the main area, and the column headers stay visible when it scrolls down.
- On a phone, the left column stacks above the agenda and the agenda scrolls inside its own area.
- The side panel closes with Escape and with its close button, and keeps focus inside while open.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The dashboard MUST show the agenda as in the design, in place of the current placeholder, keeping the welcome message shown after onboarding.
- **FR-002**: Hairdressers, working hours, lunch breaks, leaves, services and appointments MUST be generated, not read from the salon's data.
- **FR-003**: Generated content MUST be identical each time the same day is shown.
- **FR-004**: The grid MUST cover 9:00 to 19:00, and Sunday MUST show the closed salon message.
- **FR-005**: Calendar, Today, previous and next day, month navigation, hairdresser checkboxes and slot size MUST work without leaving the page.
- **FR-006**: The current time line MUST show only on today, within opening hours, and follow the clock while the page is open.
- **FR-007**: Cancelling an appointment MUST ask for confirmation first.
- **FR-008**: Bookings and cancellations MUST NOT be saved anywhere.
- **FR-009**: Every string MUST be translated in every configured locale, and dates, times and counts MUST follow the locale.
- **FR-010**: The screen MUST meet the accessibility principle (keyboard, screen reader, colour not alone, reduced motion).

### Key Entities

- **Hairdresser (generated)**: a name and a colour hue, used for the avatar and the tint of their appointment blocks.
- **Appointment (generated)**: a hairdresser, a day, a start, a duration, a client name and a service.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Side by side with the design at the same width, the screen shows no visible difference in layout, colour or spacing.
- **SC-002**: Any day of the current or a later month is reached in at most three actions from the dashboard.
- **SC-003**: The screen is fully usable at 375 pixels wide, with the keyboard alone, and with a screen reader.
- **SC-004**: No booking, cancellation or appointment is written to the database by this feature.

## Assumptions

- "Fake" covers everything in the agenda, including the hairdresser list: five generated people (Amélie, Sammy, Charlotte, Alexandra, Lucas), Alexandra unchecked at first, as in the design.
- The checkbox selection and the slot size are not remembered; a reload restores the defaults (all but Alexandra, 20 minutes).
- The week starts on Monday in the calendar, as in the design.
- The design's copy is French; English is the source wording and French is a translation.
- The top bar is the existing one; the design's top bar is not redesigned here.
- The current time line follows the real clock (the design shifts it to 12:30 for its preview).
