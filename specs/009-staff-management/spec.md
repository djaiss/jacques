# Feature Specification: Staff Management

**Feature Branch**: `2026-10-15-staff` | **Created**: 2026-10-15 | **Status**: Draft | **Input**: "Support staff."

## Clarifications (2026-10-15)

- Q: May a staff member exist without an account, against D3? → A: Yes; D3 and the constitution invariant are amended.
- Q: Beside or instead of Employee, UserLocation and TeamMemberService? → A: Instead; they are migrated and removed.
- Q: Staff hours and absences, or ScheduleRule and ScheduleException? → A: Staff hours and absences; decisions.md is amended.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The salon manages its staff (Priority: P1)

An Owner adds the people who work in the salon, edits their profile, orders them, deactivates
somebody who leaves, and decides whether each one can be booked.

**Why this priority**: nothing else in this feature, nor the schedule, exists without staff.

**Independent Test**: add Pam and Jim, reorder them, mark Jim as not bookable, deactivate Pam.

**Acceptance Scenarios**:

1. **Given** an Owner, **When** they add "Pam Beesly", receptionist, **Then** she is active, bookable and listed last.
2. **Given** Pam with a display name "Pammy", **When** she is shown, **Then** "Pammy" is used; without one, "Pam Beesly".
3. **Given** a Receptionist, **When** they try to add or edit a staff member, **Then** it is refused.

### User Story 2 - Staff work at locations and perform services there (Priority: P1)

An Owner assigns a staff member to a location and pauses that assignment. An Owner, or a
Manager of the location, declares which services the staff member performs there.

**Why this priority**: availability needs to know who works where and does what.

**Independent Test**: assign Jim to Scranton, let him perform "Haircut" there, pause the assignment.

**Acceptance Scenarios**:

1. **Given** Jim not assigned to Stamford, **When** he is set to perform a service there, **Then** it is refused.
2. **Given** "Haircut" not offered at Scranton, **When** Jim is set to perform it there, **Then** it is refused.
3. **Given** Jim removed from Scranton, **When** his services are listed, **Then** nothing at Scranton remains.

### User Story 3 - Staff have working hours (Priority: P2)

An Owner, or a Manager of the location, sets for each day of the week the time ranges a staff
member works at a location, with several ranges in a day to leave a break.

**Why this priority**: required for availability, but useless before stories 1 and 2.

**Independent Test**: give Dwight Monday 09:00 to 12:00 and 13:00 to 18:00 at Scranton.

**Acceptance Scenarios**:

1. **Given** Dwight at Scranton, **When** two Monday ranges with a gap are saved, **Then** both exist and the gap is a break.
2. **Given** Monday 09:00 to 12:00, **When** 11:00 to 14:00 is added on Monday, **Then** it is refused as overlapping.

### User Story 4 - Staff are absent (Priority: P2)

An Owner, or a Manager, records a period during which a staff member does not work, at one
location or at all of them, with an optional reason and notes.

**Why this priority**: same dependency as story 3; holidays and sick leave block the schedule.

**Independent Test**: record Michael absent everywhere for a week, then Angela at Scranton only.

**Acceptance Scenarios**:

1. **Given** Michael at two locations, **When** an absence without location is saved, **Then** it applies to both.
2. **Given** a salon in Europe/Paris, **When** an absence from 09:00 is entered, **Then** it is kept in UTC and shown as 09:00 Paris time.

### Edge Cases

- A staff member, location, service or account of another company is treated as if it did not exist.
- An inactive or not bookable staff member, or a paused assignment, keeps its data but is never offered for booking.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Authorized people MUST be able to create, read, update, reorder, deactivate and delete staff members of their company; deletion removes their assignments, services, hours and absences.
- **FR-002**: A staff member MUST have a first name and MAY have a last name, display name, email, phone, job title and biography.
- **FR-003**: A staff member MUST carry an active state, a bookable state, both on by default, and a display position.
- **FR-004**: A staff member MAY be linked to at most one account of the same company, and an account to at most one staff member.
- **FR-005**: Authorized people MUST be able to assign a staff member to a location once, pause and resume that assignment, and remove it.
- **FR-006**: A staff member MUST be able to perform a service at a location only when assigned there and the service is offered there.
- **FR-007**: Authorized people MUST be able to create, update and delete weekly working ranges for a staff member at a location they are assigned to.
- **FR-008**: A working range MUST fall on a day from 0 (Sunday) to 6 (Saturday), end after it starts, and not overlap another range of the same staff member, location and day.
- **FR-009**: Authorized people MUST be able to create, update and delete absences, each with a start, an end after it, and an optional location, reason and notes.
- **FR-010**: An absence without a location MUST apply to every location of the staff member.
- **FR-011**: Absence dates MUST be stored in UTC and shown in the time zone of the salon (D2).
- **FR-012**: Every association MUST join records of the same company, and any other attempt MUST fail as if the record did not exist.
- **FR-013**: An Owner MUST manage staff profiles and location assignments; an Owner, or a Manager on their assigned locations, MUST manage services, hours and absences.
- **FR-014**: Every create, update and delete MUST be logged and be available to the back office and the public API through the same operations.
- **FR-015**: Staff, location staff and staff location services MUST replace employees, user locations and team member services, and nothing that works today may be lost.
- **FR-016**: A Manager's location scope MUST come from the active location assignments of the staff member linked to their account.
- **FR-017**: D3, the "Salon et équipe" models of decisions.md and the matching constitution invariant MUST be amended before the code changes.

### Key Entities

- **Staff member**: a person who works for the company, optionally linked to an account; replaces employee.
- **Location staff**: a staff member assigned to a location, with an active state; replaces user location.
- **Staff location service**: a staff member performing a service offered at a location; replaces team member service.
- **Staff working hour**: one weekly time range a staff member works at a location.
- **Staff absence**: a dated period a staff member does not work, at one location or all.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An Owner adds a staff member, assigns a location and sets a week of hours in under three minutes.
- **SC-002**: 100% of cross company association attempts are refused, each backed by an automated check.
- **SC-003**: Every behaviour the replaced records support today is still covered by a passing check.
- **SC-004**: An absence entered in the salon time zone reads back identically in that time zone.

## Assumptions

- Scope is the data, rules and operations; screens and availability computation follow later.
- Permissions follow research.md R5; no production data exists yet, so nothing is migrated (R1).
- An absence may overlap another absence; a working range may cross no midnight.
- Staff keeps the avatar of the employee; the employee number is dropped; the work email becomes the email.
