# Feature Specification: Onboarding Step 3, Your Team

**Feature Branch**: `2026-10-15-onboarding-team-step` | **Created**: 2026-10-15 | **Status**: Clarified | **Input**: "Implement step 3: Your team", design `Onboarding Team.dc.html`.

## Clarifications (2026-10-15)

- Q: Is accepting an invitation part of this feature? → A: No. This feature records that an invitation is ready, with its role, and shows the email preview. Sending it and accepting it belong to a later feature.
- Q: Which role does an invited account receive? → A: The one the owner picks in the panel, a picker the design does not show.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The owner adds the people who work with them (Priority: P1)

Michael reaches "Your team" after step 2. He is already listed, marked "You", offering every
service. He adds Pam Beesly, receptionist, not bookable, and Jim Halpert, barber, who performs
the barbering services only, then continues to appointments.
**Why this priority**: it is the reason the step exists; today step 3 is a placeholder.
**Independent Test**: add Jim with three services, add Pam as not bookable, reload, continue.
**Acceptance Scenarios**:

1. **Given** a new salon, **When** step 3 opens, **Then** the owner is the only row, marked "You", "Account owner", with "All N services", and the summary says it is just them for now.
2. **Given** "Add a team member", **When** chosen, **Then** a side panel opens with first name, last name, name shown to clients, job title, email, phone, bio, the account section and the services section, every service unticked and "Clients can book appointments with this person" ticked.
3. **Given** the panel, **When** "Add to the team" is chosen with an empty first name, **Then** nothing is stored and the field says "A first name is needed."
4. **Given** Jim with three barbering services, **When** added, **Then** his row shows "Jim Halpert", "Barber · jim@dundermifflin.com" and "3 services".
5. **Given** Pam with bookable unticked, **When** added, **Then** her row shows "Not bookable" and the services list is hidden in the panel, with a note that her services are kept.
6. **Given** step 3, **When** "Continue to appointments" is chosen, **Then** step 3 is completed and step 4 opens.

### User Story 2 - The owner edits and removes team members (Priority: P1)

**Why this priority**: names, display names and services are often wrong at the first attempt.
**Independent Test**: open Dwight, set the display name "Dwight K.", untick a category, save; then remove Andy and confirm.
**Acceptance Scenarios**:

1. **Given** a row, **When** chosen, **Then** the panel opens on that person under "Edit team member" with "Save changes" and "Remove from the team".
2. **Given** Dwight with display name "Dwight K.", **When** listed, **Then** the row shows "Dwight K." and the full name "Dwight Schrute" before the job title.
3. **Given** a category checkbox, **When** ticked, **Then** every service in it is selected; the category shows "n of m" and the panel shows "n of N services selected", or "All N services selected".
4. **Given** "Select all" or "Clear", **When** chosen, **Then** every service is selected or none is.
5. **Given** "Remove from the team", **When** chosen, **Then** the panel asks "Andy Bernard leaves the team and loses their services. Sure?" and nothing goes until "Remove them" is chosen; "Keep them" cancels.
6. **Given** the owner's own row, **When** opened, **Then** the header says "Your profile", the email is read only with an explanation, and no removal nor role picker is offered.
7. **Given** "Cancel", **When** chosen with unsaved edits, **Then** the panel closes and nothing is stored.

### User Story 3 - The owner prepares invitations for colleagues (Priority: P2)

Michael ticks "Send an invitation to create an account" for Dwight, picks the Manager role,
previews the email and saves. Dwight's row shows the invitation as ready. Sending it is the work
of a later feature.
**Why this priority**: a team member can be booked without an account; the account only lets them see their own appointments and availability.
**Independent Test**: prepare an invitation for Dwight as Manager, preview it, reload, and check his row and panel keep it; check no email is sent.
**Acceptance Scenarios**:

1. **Given** a team member with no email, **When** the panel opens, **Then** the invitation checkbox is disabled and the help says an email address is needed, and that they can still be booked.
2. **Given** an email and the invitation ticked, **When** the panel is read, **Then** a role picker lists the company's roles, Staff selected by default, and the help says the invitation is kept ready for that address.
3. **Given** the invitation unticked, **When** the panel is read, **Then** the role picker is hidden.
4. **Given** an email, **When** "Preview the email" is chosen, **Then** a dialog shows sender, recipient, subject "Michael Scott invited you to join Dunder Mifflin Cuts", greeting, body naming the job title, the "Create my account" button and the footer, and closes on its button, its close control, Escape or a click outside.
5. **Given** Dwight saved with the invitation ticked and the Manager role, **When** listed, **Then** his row shows "Invitation ready", and reopening the panel shows Manager selected.
6. **Given** two members with an invitation ready, **When** the footer is read, **Then** it says "2 invitations ready."; with none, it says "No invitations prepared."
7. **Given** step 3, **When** either footer action is chosen, **Then** no email is sent and every invitation stays ready.

### User Story 4 - The owner postpones the team (Priority: P3)

**Why this priority**: a salon of one person must not be slowed down.
**Independent Test**: choose "I'll set this up later" on a salon of one person.
**Acceptance Scenarios**:

1. **Given** step 3, **When** "I'll set this up later" is chosen, **Then** step 3 is completed, step 4 opens and the team stays as stored.
2. **Given** a salon of one person, **When** the footer is read, **Then** it says the team can be added now or later from Settings.

### Edge Cases

- A bookable member with no service is shown "None yet" with a warning colour and a text, not colour alone.
- An email already used by another team member of the company is refused, and the field says why.
- An invitation ticked then the email removed is not kept: no email means no invitation.
- The Owner role may be picked: the company may have several owners (spec 001).
- A role of another company, or one deleted since the panel opened, is refused as if it did not exist.
- A not bookable member keeps their selected services, which come back when they are made bookable again.
- The owner's own record cannot be removed, made inactive, or have its email changed here.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Step 3 MUST be reachable under the onboarding rules of spec 004, and show the breadcrumb back to "Your services", the eyebrow "Step 3 of 4 · Your team", the title naming the salon, the introduction and the tracker of the design.
- **FR-002**: The list MUST show every active team member in display order, the owner first, each with avatar initials, the name shown to clients, the "You" badge for the owner, the full name when it differs, the job title, the email or "No email address", the services summary and the account state.
- **FR-003**: The account state MUST read "Account owner", "Invitation ready", "Not invited" or "No email, no account".
- **FR-004**: Adding or editing a member MUST happen in a side panel with the fields of FR-002 of spec 009; only the first name is required.
- **FR-005**: "Name shown to clients" MUST fall back to the first name when empty, and the panel header MUST show it as it is typed.
- **FR-006**: Each save, addition or removal MUST be stored at once in the real team, as in step 2; closing the panel without saving MUST store nothing.
- **FR-007**: A new member MUST be assigned to the salon's first location, and the services selected MUST be the services they perform there.
- **FR-008**: The services section MUST list every service offered at the first location by category, in catalogue order, with a checkbox per category and per service, counts, "Select all" and "Clear".
- **FR-009**: Unticking "Clients can book appointments with this person" MUST hide the services list and keep the selection.
- **FR-010**: Removing a member MUST be confirmed in the panel first, and MUST delete the member with their assignment, services and prepared invitation (no appointment exists during onboarding, D3).
- **FR-011**: The invitation checkbox MUST be available only with an email address, and the email preview only then.
- **FR-012**: A ticked invitation MUST carry one role of the company, picked in the panel, Staff by default; the picker MUST NOT be offered for the owner's own record.
- **FR-013**: A prepared invitation MUST be stored with the team member's email, the role and the inviter, and MUST NOT be sent by this feature.
- **FR-014**: The footer holding the status text, "I'll set this up later" and "Continue to appointments" MUST sit below the team list in the page flow, separated by a rule, as in step 2. It MUST NOT stay fixed to the bottom of the window.
- **FR-015**: The footer MUST count the invitations ready, and both actions MUST complete step 3 and open step 4.
- **FR-016**: The email preview MUST be shown in the interface language of the inviter, name the inviter, the salon and the job title when present, and explain why it was received.
- **FR-017**: Every change MUST be logged, counts MUST use plural rules, and the step MUST meet WCAG 2.2 AA, the panel and the dialog trapping focus and returning it on close.

### Key Entities *(include if feature involves data)*

- **Team member**: the staff record of spec 009; the owner's one exists since sign up.
- **Staff location service**: what a member performs at the first location (spec 009).
- **Invitation**: a request, not yet sent, for a team member to create an account, with the email address, the role it grants, the inviter and when it was prepared.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An owner adds a colleague with a name, an email and one category of services in under 1 minute.
- **SC-002**: No team change, invitation or role choice is lost when the owner reloads, leaves, or returns from step 4.
- **SC-003**: No email leaves the system during step 3.
- **SC-004**: An owner with no colleague leaves step 3 in one interaction.

## Assumptions

- The design's sticky footer is not followed: the footer repeats step 2's layout (user request, 2026-10-15).
- The design's "finished" card is a prototype of the transition; both footer actions open step 4, as in step 2.
- The design's footer text "will be sent when you continue" is replaced by a count of invitations ready, since nothing is sent yet.
- The role picker uses the company's roles in their usual order, each named in the reader's language (spec 001 FR-009).
- The design's sample catalogue stands for the salon's real catalogue.
- Avatar photos, working hours, absences and other locations belong to Settings, not to this step.
- Sending, expiring and accepting invitations are a later feature, which reads the invitations prepared here.
