# Feature Specification: Salon Onboarding

**Feature Branch**: `2026-10-14-salon-onboarding` | **Created**: 2026-10-14 | **Status**: Draft

**Input**: User description: "After seeing the welcome screen, the user who created a new company
account enters an onboarding process (/onboarding) of four steps, each with its own address and
page. The onboarding can be exited at any time, and the step the user was on is saved. Once the
onboarding is done, it is marked as done on the account." Design for the first step: claude.ai
design project `Onboarding Salon.dc.html`. The other three steps will be designed later.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new salon owner sets up their salon (Priority: P1)

After the welcome screen, the owner lands on the first step, "Location": the shared header
(breadcrumb, step counter, title, "Finish later", tracker of the four steps) and the form "Set up
your salon". Pressing "Create my salon" saves the salon and its first location, then shows the
confirmation "{salon name} is ready".

**Why this priority**: a salon cannot take an appointment without a location and a time zone
(decision D2), and it is the only step designed today.

**Independent Test**: sign up, dismiss the welcome screen, submit the form, and check the
company name, the first location and the tracker.

**Acceptance Scenarios**:

1. **Given** a company whose welcome screen was just dismissed, **When** the owner presses its button, **Then** step 1 shows instead of the home screen.
2. **Given** step 1, **When** the owner submits a salon name, a country and a time zone, **Then** the company is renamed, a first location is created with every value entered, and the confirmation shows.
3. **Given** step 1, **When** the salon name is empty, **Then** nothing is saved and the field shows "The salon needs a name."
4. **Given** step 1, **When** the owner changes the country, **Then** the time zone list offers only that country's time zones, with the first one selected.
5. **Given** the confirmation, **When** the owner presses "Edit the salon" and submits again, **Then** the same location is updated and no second one is created.
6. **Given** the confirmation, **When** the owner presses "Continue to services", **Then** step 2 shows at its own address.

### User Story 2 - The owner leaves and comes back where they stopped (Priority: P1)

On any step, the owner presses "Finish later" and reaches the home screen. Later, opening the
onboarding brings them back to the furthest step they reached.

**Why this priority**: leaving at any time is a requirement of the request.

**Independent Test**: reach step 2, press "Finish later", sign out and in, open `/onboarding`,
and check that step 2 shows.

**Acceptance Scenarios**:

1. **Given** any step, **When** the owner presses "Finish later", **Then** the home screen shows and the saved step is unchanged.
2. **Given** an onboarding saved on step 3, **When** the owner opens `/onboarding`, **Then** step 3 shows.
3. **Given** an onboarding saved on step 2, **When** the owner opens the address of step 4, **Then** they are sent to step 2.
4. **Given** an onboarding saved on step 3, **When** the owner opens step 1, **Then** it shows with its saved values.

### User Story 3 - The onboarding is finished once and for all (Priority: P2)

Completing the fourth step marks the company as onboarded and leads to the home screen.

**Independent Test**: go through the four steps and check that the onboarding addresses then
lead to the home screen.

**Acceptance Scenarios**:

1. **Given** step 4, **When** the owner completes it, **Then** the company is marked as onboarded and the home screen shows.
2. **Given** an onboarded company, **When** any of its users opens an onboarding address, **Then** they reach the home screen.

### Edge Cases

- The owner closes the browser on any step: the furthest step reached stays saved.
- Step 1 submitted from two tabs: the company still has a single first location.
- A user who is not the owner never enters the onboarding and is not sent to it.
- A company that has not dismissed its welcome screen is sent there first, not to the onboarding.
- The time zone saved always belongs to the country saved.
- The screens work on a phone, with the keyboard alone, with a screen reader, and in dark mode.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A company MUST record the furthest onboarding step reached (1 to 4) and whether its onboarding is finished.
- **FR-002**: A newly created company MUST start on step 1, not finished. Every company that exists before this feature MUST be marked as finished.
- **FR-003**: Dismissing the welcome screen MUST take the owner to the saved onboarding step instead of the home screen.
- **FR-004**: The four steps Location, Services, Staff and Appointments MUST each have their own address under `/onboarding`, and `/onboarding` itself MUST lead to the saved step.
- **FR-005**: Completing a step MUST save the next one as reached. Opening a step beyond the saved step MUST lead to the saved step; any step up to it can be revisited.
- **FR-006**: Every step MUST show "Finish later", which leads to the home screen without changing the saved step.
- **FR-007**: Once "Finish later" is pressed, the owner MUST be able to use the application freely and MUST NOT be sent back to the onboarding. The way back into an unfinished onboarding will be defined later. Until then, the onboarding address is the way to resume it.
- **FR-008**: Step 1 MUST match the design: breadcrumb, step counter, title with the salon name, subtitle, tracker showing done, current and upcoming steps, and the form with its help text and button.
- **FR-009**: Step 1 MUST require a salon name, a country and a time zone, and accept the street address, city, province or state, postal code and phone number as optional.
- **FR-010**: Submitting step 1 MUST create the company's first location the first time and update that same location afterwards. The salon name MUST rename the company and name the first location, on every submission.
- **FR-011**: The time zone list MUST offer only the time zones of the selected country, and changing the country MUST select its first time zone.
- **FR-012**: After step 1 is submitted, the page MUST show the confirmation with "Continue to services" and "Edit the salon".
- **FR-013**: Steps 2, 3 and 4 MUST exist with the shared header and a placeholder body holding a button to complete the step.
- **FR-014**: Completing step 4 MUST mark the onboarding as finished and lead to the home screen. A finished onboarding MUST NOT be shown again, and its addresses MUST lead to the home screen.
- **FR-015**: Only the owner of the company MUST be able to open the onboarding.
- **FR-016**: Every text on these screens MUST be translated in every configured locale, the content written by the salon excepted.
- **FR-017**: The screens MUST meet WCAG 2.2 AA on desktop, tablet and phone, in light and dark mode.

### Key Entities

- **Company**: gains the furthest onboarding step reached and whether its onboarding is finished. The request calls it the account.
- **Location**: the first location, created by step 1, with its name, address, country, phone number and time zone.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of new companies reach step 1 right after the welcome screen.
- **SC-002**: An owner completes step 1 in under 2 minutes.
- **SC-003**: 100% of owners who leave the onboarding return to the furthest step they reached.
- **SC-004**: 0 companies see the onboarding again once it is finished, or if they existed before the release.
- **SC-005**: 0 companies end up with two first locations after submitting step 1 more than once.

## Assumptions

- "Account" is the company, as for the welcome screen. Only the owner goes through the onboarding.
- The five countries and phone examples of the design are sample data: every country is offered.
- The first location is marked as the headquarters of the company.
- There is no "skip this step" action. Steps 2 to 4 save nothing besides the step reached.
- The entry point offering to resume an unfinished onboarding is out of scope, designed later.
- The company name and the first location's name are written together by step 1 only. Later changes to either one, from the settings, do not change the other.
- The screens sit inside the usual application frame, with the top bar, as the design shows.
- No API coverage: the onboarding is a back office flow, and the location it creates is an ordinary one.
