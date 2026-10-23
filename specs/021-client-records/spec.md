# Feature Specification: Client records

**Feature Branch**: `2026-10-23-client-records` | **Created**: 2026-10-23 | **Status**: Draft

**Input**: User description: "sur la base de cette spec, ajoute les modèles nécessaires pour gérer un client dans Jacques", followed by a functional note modelled on Planity Pro: a client record (identity and contact details) and a salon file (habits, history, preferences, technical notes, indicators).

## User Scenarios & Testing *(mandatory)*

This feature builds the data foundation only. Screens, actions and API endpoints that read or change it come in later features. Each story is therefore tested on the data and its rules.

### User Story 1 - The salon keeps one record per client (Priority: P1)

A salon records a client met on the phone or at the desk: first name, last name, mobile phone, and optionally email, gender, birth date and postal address. The record belongs to the company, so every location of the company sees the same client. It can name a preferred team member, carry special instructions ("no scented product"), say whether the client receives SMS reminders, block the client from booking online, and keep the history of their marketing consent.

**Why this priority**: every other part of the salon file hangs on this record.

**Independent Test**: create a client in one company, then check it is invisible from another company and that a second record with the same phone, written differently, is refused.

**Acceptance Scenarios**:

1. **Given** a company, **When** a client is recorded with "+1 570 555 0100", **Then** a second client with "(570) 555-0100" in the same company is refused as the same number.
2. **Given** two companies, **When** each records the same phone, **Then** both records exist, one per company.
3. **Given** a client with online booking blocked, **When** the flag is read, **Then** it says the client cannot book online.
4. **Given** SMS marketing consent granted then withdrawn, **When** the consent history is read, **Then** both changes show in order, with date and author, and the current state is withdrawn.

### User Story 2 - The salon keeps the history of visits and services (Priority: P1)

Each appointment names the client who receives the service, the team member, the location, its start and end, and its status (scheduled, attendance confirmed, cancelled, no show). It lists the services performed, in order, each with the name, price and duration it had at the time, so a later catalogue change never rewrites the past.

**Why this priority**: the history and the indicators of story 4 come from it.

**Independent Test**: record an appointment with two services, change the catalogue price, and check the appointment keeps the old price.

**Acceptance Scenarios**:

1. **Given** an appointment for Michael Scott, **When** the price of Men's Haircut changes in the catalogue, **Then** the appointment still shows the price it was booked at.
2. **Given** a past appointment that is neither cancelled nor a no show, **When** its status is read, **Then** it counts as a visit.
3. **Given** Jan Levinson books for her daughter Astrid from her own phone, **When** the appointment is recorded, **Then** it sits on Jan's record, as D18 accepts.

### User Story 3 - The salon keeps notes and technical notes (Priority: P2)

A team member adds dated notes to a client's file, signed with their name. After a service, they record a technical note attached to that service of that appointment: "Color formula 6.1 + 7.0 (1:1), developer 20 vol, 35 min, natural cool brown, root touch up next visit", signed by Kelly Kapoor. All technical notes of a client read in date order across all their appointments.

**Why this priority**: formulas are what a colourist needs from one visit to the next.

**Independent Test**: record two colourings with a technical note each and list the client's technical notes newest first.

**Acceptance Scenarios**:

1. **Given** a performed service, **When** a technical note is recorded, **Then** it names the service, the appointment date and its author.
2. **Given** the author has left the salon, **When** the note is read, **Then** the note and the author's name are kept.

### User Story 4 - The owner reads the indicators of a client (Priority: P2)

From the history, a client's file gives: total visits, lifetime spending (sum of the prices recorded on the services of their visits), last visit and its main service, average interval between visits, favourite service, next appointment, and a segment: New, Regular or Inactive.

**Why this priority**: lets the owner spot regulars, big spenders and clients who stopped coming.

**Independent Test**: build a client with known visits and check each indicator and the segment.

**Acceptance Scenarios**:

1. **Given** visits on Jan 1, Feb 5 and Mar 12, **When** the average interval is read, **Then** it is five weeks.
2. **Given** a client whose last visit is more than six months old, **When** the segment is read, **Then** it is Inactive.
3. **Given** a client without any visit, **When** the indicators are read, **Then** counts are zero and dates are empty, never an error.

### Edge Cases

- A phone is compared only after normalization to the international format, using the country of the location where it is entered when no prefix is given.
- A preferred team member who is deactivated stays on the record; one who is deleted leaves it empty.
- An erasure request (D23) empties name, phone, email, address, birth date, special instructions, notes and technical notes, and keeps appointments and their services.
- A cancelled or no show appointment never counts as a visit, nor in spending.
- An appointment of a client of another company can never be attached to this client.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A client MUST belong to exactly one company, and MUST be invisible from any other company.
- **FR-002**: A client MUST have a first name, a last name and a phone; email, gender, birth date and postal address MUST be optional.
- **FR-003**: A phone MUST be stored normalized and MUST be unique within a company (D18).
- **FR-004**: A client MUST NOT require a user account.
- **FR-005**: A client MUST carry a preferred team member (optional), special instructions (optional), an SMS reminder preference and an online booking block.
- **FR-006**: An appointment MUST carry its company, location, client, team member, start and end in UTC, and one of the four statuses of D12.
- **FR-007**: An appointment MUST list its services in order, each with the name, price, currency and duration recorded at booking.
- **FR-008**: A note MUST carry its client, author, date and text.
- **FR-009**: A technical note MUST be attached to one service of one appointment, and MUST carry its author, date and text.
- **FR-010**: The indicators of story 4 MUST be computed from the history, never stored or typed by hand.
- **FR-011**: A consent change MUST carry its client, channel, state, date and author, and MUST never be edited afterwards.
- **FR-012**: Every personal field listed in the erasure edge case MUST be emptiable without losing the client's appointments; the erasure operation itself comes with the client screens.
- **FR-013**: A client record MUST NOT hold photos or attachments (D19).

### Key Entities

- **Client**: a person served by the company, identified by their phone, with contact details and preferences.
- **Client note**: a dated, signed text about a client.
- **Appointment**: a booked time of one client with one team member in one location, with a status.
- **Appointment service**: a service performed in an appointment, with its historical name, price and duration.
- **Technical note**: a dated, signed text attached to one appointment service, replacing the `ColorFormula` of decisions.md.
- **Client consent**: one change of a client's marketing consent on one channel.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: No two clients of a company share a phone, whatever the way it was typed.
- **SC-002**: A catalogue change leaves 100% of past appointment prices and durations unchanged.
- **SC-003**: Every indicator of story 4 matches a hand count on a client with 24 visits.
- **SC-004**: With every personal field of a client emptied, their appointments and services remain and their visits still count.

## Assumptions

- Lifetime spending sums catalogue prices recorded on visits. It is not a record of what was paid, so it respects D22, and it cannot exclude tips or discounts.
- Segments use fixed thresholds: New below two visits, Inactive after six months without a visit, Regular otherwise. Configurable thresholds would be a new settings page, against D14.
- Hair preferences, preferred products and products to avoid are written in notes or special instructions, not in dedicated fields; no `client_preferences` table.
- Appointment steps and slot holds (`AppointmentStep`, `AvailabilityHold`) come with the booking feature.
- Out of scope: photos and attachments (D19), separate booker and recipient (D18), product purchases (D24), payments (D22), gift cards, packages, loyalty, campaigns, tags, duplicate merging, client accounts.
- Access rules for notes and technical notes are decided with the screens that show them.
