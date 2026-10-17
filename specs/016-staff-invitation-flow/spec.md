# Feature Specification: Staff invitation flow

**Feature Branch**: `2026-10-17-staff-invitation-flow` | **Created**: 2026-10-17 | **Status**: Draft

**Input**: User description: "An owner adds Sophie to the team, picks her role, and Sophie receives an email that takes her straight to her workspace. Keep the invitation prepared during onboarding step 3 (specs/012), send it explicitly, accept it on a public page, track it from Settings."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sophie accepts an invitation with a new account (Priority: P1)

Sophie receives an email naming who invites her, the salon and her role, with one button: Accept invitation. The link opens a public page (no dashboard, no owner onboarding) showing the salon, her name, her email address and her role. She only chooses a password, then lands in her workspace with a welcome message.

**Why this priority**: without it, no invitation reaches anybody and no team member signs in.

**Independent Test**: send an invitation to a staff member with no account, open the link, set a password, and see the welcome message.

**Acceptance Scenarios**:

1. **Given** a pending invitation for Sophie Martin at Studio Camille as Staff, **When** the email is delivered, **Then** it names Camille, Studio Camille and Staff, says it expires in 7 days, and holds a single action.
2. **Given** that link, **When** Sophie sets a valid password, **Then** an account is created with her name and email, linked to her staff record, given her role, and the invitation is accepted.
3. **Given** an accepted invitation, **When** its link is opened again, **Then** a page says it was already used and offers to sign in.
4. **Given** an expired or revoked link, **When** it is opened, **Then** a page says so in plain words and tells Sophie to ask the salon for a new one.


### User Story 2 - The owner tracks and sends invitations from Settings (Priority: P1)

A Team members screen in Settings lists every staff member with their email, role and invitation state, with the actions that state allows.

| State | Meaning | Actions |
| --- | --- | --- |
| Prepared | Kept ready, never sent | Send, Cancel |
| Pending | Sent, not accepted, not expired | Resend, Revoke |
| Accepted | An account is linked | Manage access |
| Expired | Sent more than 7 days ago | Send new invitation |
| Revoked | Withdrawn by the salon | Prepare new invitation |

**Why this priority**: the owner must not have to remember what they sent.

**Independent Test**: prepare an invitation, then send, resend and revoke it from Settings, checking the state shown and which links still work.

**Acceptance Scenarios**:

1. **Given** a prepared invitation, **When** the owner presses Send, **Then** it becomes Pending with its send date, and one email is queued.
2. **Given** a pending invitation, **When** the owner presses Resend, **Then** a new link is emailed and the previous link stops working.
3. **Given** a pending invitation, **When** the owner revokes it after confirming, **Then** its link stops working and it shows as Revoked.
4. **Given** the owner presses Send twice quickly, **When** the requests are processed, **Then** one email is sent.
5. **Given** a user of another company, **When** they act on this invitation, **Then** it fails as if it did not exist.


### User Story 3 - The owner sends prepared invitations at the end of onboarding (Priority: P2)

Step 3 still prepares invitations without sending anything. Finishing step 4 shows a completion screen with an "Invite your team" section, the number of prepared invitations and a Send invitations button. Skipping it leaves them prepared, to send later from Settings.

**Why this priority**: the owner may still change the configuration during step 4, so nobody is contacted before it is done.

**Independent Test**: prepare two invitations in step 3, finish step 4, press Send invitations, and see both Pending.

**Acceptance Scenarios**:

1. **Given** two prepared invitations, **When** the owner finishes step 4, **Then** the completion screen shows 2 invitations ready and sends nothing on its own.
2. **Given** no prepared invitation, **When** the owner finishes step 4, **Then** the section is not shown.


### User Story 4 - Somebody with an account, or signed in, opens the link (Priority: P2)

**Acceptance Scenarios**:

1. **Given** an account already uses the invited email, **When** the link is opened signed out, **Then** the page asks to sign in instead of creating an account, and after signing in shows a confirmation to join.
2. **Given** the right person is signed in, **When** the link is opened, **Then** a single confirmation joins the team.
3. **Given** somebody else is signed in, **When** the link is opened, **Then** the page names the invited address and asks to switch accounts, and links nothing.
4. **Given** the existing account belongs to another salon, **When** the link is opened, **Then** the page explains that this address is already used with another salon and that Sophie must be invited with another address, and links nothing (D3: one company per account).

### Edge Cases

- The staff email changes while Pending: the link stops working and the invitation goes back to Prepared.
- The staff member is deleted or deactivated: the link stops working.
- The role is deleted or belongs to another company: sending and accepting are refused.
- Two acceptances of the same link at once: exactly one succeeds; the other sees "already used".
- A staff member without an email address cannot be invited (as today).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Preparing an invitation (onboarding step 3 or Settings) MUST NOT send any email.
- **FR-002**: Sending MUST happen only on an explicit action: Send, Resend, Send new invitation, or Send invitations on the completion screen.
- **FR-003**: Each send MUST issue a new random single use token, store only its fingerprint, and invalidate every earlier token of that invitation.
- **FR-004**: A link MUST expire 7 days after it is sent.
- **FR-005**: The email MUST be delivered in the background, and a repeated click or a retried delivery MUST NOT produce a second email for the same send.
- **FR-006**: A staff member's invitation MUST be sent at most 3 times in 24 hours; beyond that the action is refused with an explanation.
- **FR-007**: Only a user allowed to manage roles in that company MUST be able to prepare, send, resend, revoke or cancel an invitation.
- **FR-008**: Acceptance MUST verify that the authenticated or created account's email equals the invited email.
- **FR-009**: Acceptance MUST, in one indivisible operation, link the account to the staff record, give the role, and mark the invitation accepted.
- **FR-010**: An accepted, expired, revoked or invalidated link MUST show a page naming that case, without revealing data about the salon to an unknown visitor beyond its name.
- **FR-011**: After acceptance the user MUST NOT enter the owner onboarding; they land on the workspace their permissions allow, with a welcome message naming the salon.
- **FR-012**: Settings MUST list every staff member with their invitation state and only the actions of that state (US2 table).
- **FR-013**: Revoke and Cancel MUST ask for confirmation first.
- **FR-014**: Each send, resend, revoke, cancel and acceptance MUST be logged as a user action.
- **FR-015**: Every string MUST be translated in every configured locale, and every screen meets WCAG 2.2 AA and works on a phone.

### Key Entities

- **Staff invitation**: one per staff member, with its role and author (existing), plus token fingerprint, sent, expiry, acceptance and revocation dates. Its state derives from these dates.
- **Staff** and **User**: unchanged; acceptance links the created or reused account to the staff record.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A new team member goes from the email to their workspace in under 1 minute, typing only a password.
- **SC-002**: 100% of superseded, revoked, expired or used links are refused.
- **SC-003**: No email is ever sent during onboarding steps 3 and 4.
- **SC-004**: The owner reads the state of every invitation on one screen, without opening any record.

## Assumptions

- The four default roles of D4 apply. No personal calendar or salon agenda exists yet, so every role lands on the dashboard with the welcome message; the role based destination arrives with those screens.
- "Manage access" opens the existing role screen of that person.
- The email follows the existing mail layout; no invitation email exists yet, so it is new.
- Tests are written with Pest (specs/014), not PHPUnit as the input says. The salon name shown is the company name.
