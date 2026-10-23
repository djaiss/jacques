# Feature Specification: Clients screen

**Feature Branch**: `2026-10-23-clients-screen` | **Created**: 2026-10-23 | **Status**: Draft

## Clarifications (2026-10-23)

- Q: Show the Spent column and the Top clients tab of the design? → A: No, both are dropped.
- Q: Where does the screen sit in the top menu? → A: Behind the existing Clients tile of the company menu (specs/018-company-menu), which gets its address.

**Input**: User description: "Je veux un écran qui liste les clients dans le compte", with the design `Clients.dc.html` (claude.ai/design project 0592e1b2). No client creation or other action yet, only this screen, reachable from the top menu.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The salon reads every client of the account (Priority: P1)

The Clients tile of the company menu leads to the screen. It shows a breadcrumb back to the dashboard, the title, a sentence saying a client belongs to the account and can be seen in several locations, and one row per client: initials avatar, full name, phone, location of the last visit, date of the last visit with how long ago it was, the next appointment (or "None booked") and the number of visits. Rows are sorted by last visit, most recent first. A footer gives how many clients are shown.

**Why this priority**: the list is the screen; every other story filters it.

**Independent Test**: with Pam Beesly (three visits, one coming up) and Kevin Malone (one visit in March, nothing booked), open Clients from the menu and read both rows.

**Acceptance Scenarios**:

1. **Given** Michael holds `client.view` on the company, **When** he follows the Clients tile, **Then** the screen lists Pam before Kevin, Pam's next appointment shows its date and Kevin's reads "None booked".
2. **Given** a client of another company, **When** the screen is shown, **Then** they never appear.
3. **Given** the company has no client, **When** the screen opens, **Then** it says "Clients appear here after their first booking."
4. **Given** a person without `client.view`, **When** they open the menu, **Then** the Clients tile is drawn and leads nowhere, as specs/018 does for Services and Team; **When** they open the screen's address, **Then** they get not found.

### User Story 2 - The salon finds one client (Priority: P1)

A search field matches the name, the email or the phone number; a location picker narrows the list to clients who have an appointment at that location.

**Why this priority**: the reason to open a client list is usually to find one person.

**Independent Test**: search "angela", "0100" and "dwight schrute" and read the results.

**Acceptance Scenarios**:

1. **Given** Angela Martin is a client, **When** "angela" is searched, **Then** only she is listed.
2. **Given** her number is +1 570 555 0100, **When** "555 0100" or "(570) 555-0100" is searched, **Then** she is listed.
3. **Given** Dwight Schrute is a client, **When** "dwight schrute" or "Schrute" is searched, **Then** he is listed; accents and case never prevent a match.
4. **Given** a search that matches nobody, **When** it is submitted, **Then** the list says "Nobody matches “xyz” across all locations." (or "at Scranton" when a location is picked).
5. **Given** the location picker is set to Scranton, **When** the list is shown, **Then** only clients with an appointment at Scranton appear, and the footer says "at Scranton".

### User Story 3 - The salon reads a segment of its clients (Priority: P2)

Tabs above the list each show how many clients they hold under the current search and location, and a sentence saying what they hold. New, Regular and Inactive are the segments of specs/021-client-records (US4 and Assumptions), never redefined here.

| Tab | Holds | Empty text |
| --- | --- | --- |
| All clients | every client | Clients appear here after their first booking. |
| New clients | segment New | Nobody is a new client right now. |
| Regular clients | segment Regular | Nobody is a regular client yet. |
| Inactive clients | segment Inactive | Every client came back in the last six months. |
| Not rebooked | no appointment coming up | Every client has an appointment coming up. |

**Why this priority**: useful for follow up, but finding a client works without it.

**Independent Test**: with the Dunder Mifflin sample data, open each tab and compare its rows with each client's segment and next appointment.

**Acceptance Scenarios**:

1. **Given** Kevin's last visit was seven months ago, **When** Inactive clients is opened, **Then** Kevin is listed and Pam is not.
2. **Given** a search is typed, **When** the tabs are shown, **Then** each count reflects the search.
3. **Given** a tab is opened, **When** the address is copied and opened again, **Then** the same tab, search and location are shown.

### Edge Cases

- A client with no visit yet (only future or cancelled appointments): Last visit reads "No visit yet", the client is New, and is under Not rebooked only when nothing is coming up.
- A client seen in several locations: the Location column names the location of the last visit; the picker finds them under each location they have an appointment at.
- A deactivated location: its past visits still count; it is not offered in the picker.
- An erased client (D23) has no name or phone: the row reads "Erased client", and search never matches it.
- Dates are shown in the time zone of the appointment's location and in the viewer's locale (D2).
- A very long name is cut with an ellipsis; the full name stays readable by a screen reader.
- Thousands of clients: the list is paginated, and counts stay exact.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The Clients tile of the company menu MUST lead to the screen for a person holding `client.view` at any scope, and stay inert otherwise.
- **FR-002**: The screen MUST list the company's clients with the columns of US1, sorted by last visit, most recent first, clients without a visit last.
- **FR-003**: Visits, last visit, next appointment and segment MUST be the indicators of specs/021-client-records, so this list and the client file never disagree.
- **FR-004**: Search MUST match the first name, the last name, the full name and the email ignoring case and accents, and the phone by its digits whatever the format typed.
- **FR-005**: The location picker MUST offer "All locations" and every active location of the company.
- **FR-006**: The five tabs of US3 MUST each show their count under the current search and location, and their own description and empty text.
- **FR-007**: Tab, search, location and page MUST be part of the address.
- **FR-008**: The list MUST be paginated by 50 rows; the footer MUST say how many clients match, with the plural handled per locale.
- **FR-009**: `client.view` on the company MUST show every client; on self, only the clients the viewer has performed an appointment for. Without it, the screen MUST answer not found.
- **FR-010**: The screen MUST offer no action on a client: no "Add a client" button, and rows are not links until the client screens exist.
- **FR-011**: Every string MUST be translated in every configured locale; the screen meets WCAG 2.2 AA, the tabs and the picker work with the keyboard, the next appointment state does not rely on the dot colour alone, and the screen works on a phone.

### Key Entities

- **Client**, **Appointment**, **Appointment service**, **Location**: existing (specs/021-client-records). No new entity.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: From any screen, the salon reaches a given client's row in two clicks and one search.
- **SC-002**: The list and its counts show in under one second for a company of 10,000 clients.
- **SC-003**: 100% of attempts to see another company's clients, or without `client.view`, are refused.
- **SC-004**: For every client of the sample data, the segment, visit count, last visit and next appointment shown match the client's indicators.

## Assumptions

- Client creation, the client file and the "Add a client" button come with the client screens announced by specs/021.
- The design's "Salon" is a location in Jacques' vocabulary; its names and figures are illustrative.
- The design's segment thresholds (30 days, three visits in six months, 90 days) give way to those of specs/021.
- The design mixes French and English descriptions; English is the source, translated as usual.
- Self scope on `client.view` means the clients of the viewer's own appointments, like the self scope of appointments.
