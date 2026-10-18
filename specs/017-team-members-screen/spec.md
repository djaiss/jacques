# Feature Specification: Team members screen

**Feature Branch**: `2026-10-18-team-members-screen` | **Created**: 2026-10-18 | **Status**: Draft

## Clarifications (2026-10-18)

- Q: Is a removed account deleted or deactivated? → A: Deleted; the staff record stays.
- Q: One role per person or several? → A: One, picked from the company's roles; D4 is amended.
- Q: May permissions change for one person? → A: No; the panel shows the role's permissions read only.
- Each tab has its own address, controller and view model (asked during implementation).

**Input**: User description: "Je veux un meilleur écran pour gérer les employés", with the design `Team Members.dc.html` (claude.ai/design project 0592e1b2).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The owner reads the whole team at a glance (Priority: P1)

Settings › Team members shows one row per active team member: avatar, name, email, a "You" badge on the signed in person, their role, their account state, and a Manage button. Tabs above the list (Everybody, Signs in, Invited, No account) each have their own address and show how many people they hold.

**Why this priority**: every other story starts from this list.

**Independent Test**: with Michael (owner, signs in), Pam (prepared invitation) and Toby (no account), check each row and each filter count.

**Acceptance Scenarios**:

1. **Given** Michael is signed in, **When** he opens the screen, **Then** his row carries the "You" badge and the counts read Everybody 3, Signs in 1, Invited 1, No account 1.
2. **Given** the tab Invited, **When** it is opened, **Then** only Pam is listed, and the address names the tab.
3. **Given** a tab that holds nobody, **When** it is opened, **Then** the list says "Nobody matches this filter right now."
4. **Given** a team member of another company, **When** the screen is shown, **Then** they never appear.

### User Story 2 - The owner manages one person's account from a side panel (Priority: P1)

Manage opens a side panel titled with the person's name and email. Its Account section names the state and offers only the actions of that state.

| State | Shown as | Actions |
| --- | --- | --- |
| No account, email known | No account | Send an invitation, Prepare it for later |
| Prepared | Invitation not sent | Send the invitation, Cancel the invitation |
| Pending | Invitation sent | Send it again, Cancel the invitation |
| Expired | Invitation expired | Send a new invitation |
| Signs in, not the viewer | Signs in | Remove the account |

**Why this priority**: it replaces the per row buttons of specs/016 with one place per person.

**Independent Test**: open Toby's panel, prepare, send, resend and cancel his invitation, checking the state after each step.

**Acceptance Scenarios**:

1. **Given** Toby has no account, **When** the owner presses Send an invitation, **Then** the invitation is sent with the role chosen in the panel and the state reads Invitation sent.
2. **Given** Jim signs in, **When** the owner presses Remove the account, **Then** a warning says Jim can no longer sign in but stays on the team and can still be booked, and nothing happens until Remove it is pressed.
3. **Given** the panel of the viewer, **When** it opens, **Then** Remove the account is not offered.

### User Story 3 - The owner sets a person's role (Priority: P2)

The panel lists the company's roles as cards, one selectable at a time, each with its description, and below them, read only and grouped, the permissions the chosen role grants. Save changes stores the role and shows "The access of Jim Halpert is saved."; Cancel discards.

**Why this priority**: valuable, but the list and the account actions work without it.

**Independent Test**: move Jim from Staff to Manager, save, and see Manager on his row.

**Acceptance Scenarios**:

1. **Given** Jim is Staff, **When** Manager is picked, **Then** the permissions switch to what Manager grants and no change is stored before Save.
2. **Given** any role is picked, **When** the permissions are shown, **Then** none can be ticked or unticked.
3. **Given** Michael is the only active owner, **When** his panel opens, **Then** a notice explains why, and every role other than Owner is disabled.

### Edge Cases

- Somebody else changes the same person between opening the panel and Save: the later save wins, and the list shows the stored result.
- A staff member without an email: the Account section says to add one to their profile, and offers no invitation action.
- Too many sends in 24 hours (specs/016 FR-006): the refusal is shown inside the panel.
- Closing the panel with unsaved changes discards them; the panel says so on Cancel only if something changed.
- Removing the account of a person with appointments keeps the person and the appointments; only the account and its sign in are deleted.
- A person holding several roles before this change keeps the first one shown today, once D4 is amended.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The screen MUST list every active staff member of the company with avatar, name, email, role, account state and a Manage action.
- **FR-002**: The tabs Everybody, Signs in, Invited (prepared, pending, expired) and No account (none, revoked) MUST each have their own address and show their count.
- **FR-003**: Manage MUST open a side panel per person holding the Account, Role and Permissions sections.
- **FR-004**: The Account section MUST offer only the actions of the person's state (US2 table), each reusing the invitation operations of specs/016.
- **FR-005**: Cancelling an invitation and removing an account MUST ask for confirmation first.
- **FR-006**: Removing an account MUST delete the account, keeping the staff record, its appointments and its bookable state; the person can be invited again.
- **FR-007**: The Role section MUST let the viewer pick exactly one of the company's roles for the person.
- **FR-008**: The Permissions section MUST show, read only, what the chosen role grants, grouped as the role editor groups them, with a link to edit that role.
- **FR-009**: A role change MUST be stored only on Save changes, which shows a confirmation message on the list.
- **FR-010**: The company MUST always keep one active owner; the panel MUST prevent the last one from leaving the Owner role.
- **FR-011**: Only a user allowed to hand out roles MUST be able to open the panel or act in it; everybody else gets not found.
- **FR-012**: Each role change, invitation action and account removal MUST be logged as a user action.
- **FR-013**: Every string MUST be translated in every configured locale; the screen and the panel meet WCAG 2.2 AA, trap and return focus, close with Escape, and work on a phone.
- **FR-014**: D4 in decisions.md MUST be amended (one role per person) before the code changes, and the role editor's people tab follows it.

### Key Entities

- **Staff**, **User**, **Role**, **Staff invitation**: existing (specs/009, specs/016). A user holds exactly one role (FR-007).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The owner finds who cannot sign in yet in one click, at an address they can bookmark.
- **SC-002**: Inviting a person and setting their role takes one panel and under 30 seconds.
- **SC-003**: 100% of attempts on another company's people, or by somebody not allowed to hand out roles, are refused.
- **SC-004**: No company is ever left without an active owner.

## Assumptions

- The screen replaces the current Settings › Team members screen of specs/016; the URL stays.
- The design's sample permission list is illustrative; the real list is the existing permissions and groups.
- The design's sentence "Owners and managers can change who sees what" contradicts D4 and becomes "Only owners can change who sees what."
- Expired and revoked invitations, absent from the design, follow the specs/016 table.
- Deactivated staff stay off this screen, as today.
