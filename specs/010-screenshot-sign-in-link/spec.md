# Feature Specification: Screenshot Sign In Link

**Feature Branch**: `2026-10-15-screenshot-sign-in-link`

**Created**: 2026-10-15

**Status**: Draft

**Input**: User description: "Add a command that mints a short lived signed link which signs somebody in, so screens behind the sign in can be captured with Iris. Adapt the take-screenshot skill, using Michael Scott and The Office (US) instead of Monica Geller. Make sure no reference to the TV show Friends is used in tests and samples."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Capture a screen behind the sign in (Priority: P1)

Iris opens one URL in a fresh browser and carries no session, so signing in first and capturing
afterwards does not work. A developer (or an agent) runs one command naming the account (Michael
Scott, the seeded account) and the screen to land on. The command prints a link. Iris opens it, is
signed in as Michael Scott, lands on the screen and captures it.

**Independent Test**: mint a link for `michael.scott@dundermifflin.com` to the settings screen,
open it in a browser without a session, and confirm it lands there signed in as Michael Scott.

**Acceptance Scenarios**:

1. **Given** a development instance with the feature switched on, **When** the developer runs the
   command with an address and a path, **Then** it prints one link, the account and the lifetime.
2. **Given** a link that has not expired, **When** it is opened, **Then** the browser is signed in
   as that account and lands on the path.
3. **Given** no path was given, **When** the link is opened, **Then** it lands on the root.
### User Story 2 - Refuse where a link would be unsafe or useless (Priority: P1)

The link signs somebody in without a password, so it must only ever work on a developer's own
instance. The command refuses, in one sentence naming the cause, when the feature is switched off,
when the instance runs in production, when it does not answer on a development host, or when the
account does not exist.

**Independent Test**: run the command once per refusal condition and confirm a failure, a message
naming the cause, and no link.

**Acceptance Scenarios**:

1. **Given** the setting is off, **When** the command runs, **Then** it fails and names the setting.
2. **Given** production, setting on, **When** the command runs or a link is opened, **Then** both
   are refused.
3. **Given** the instance answers on `dundermifflin.com`, **When** the command runs, **Then** it
   fails and names that host.
4. **Given** no account uses `toby.flenderson@dundermifflin.com`, **When** the command runs for
   it, **Then** it fails and names the address.
### User Story 3 - Reject an expired or altered link (Priority: P2)

A link stops working after a few minutes, and any edit to it makes it invalid.

**Independent Test**: change the address in a link from Michael Scott to Dwight Schrute and open
it; wait past the lifetime of another and open it. Nobody is signed in either time.

**Acceptance Scenarios**:

1. **Given** an expired link, **When** it is opened, **Then** it is refused.
2. **Given** a link whose address, path or expiry was edited, **When** it is opened, **Then** it is
   refused.
3. **Given** a link whose path points to another site, **When** it is opened, **Then** the person
   lands inside the application, never on the other site.
### User Story 4 - Correct screenshot instructions, The Office only (Priority: P3)

The screenshot instructions describe this command, with examples using the seeded account, the
host this project is served on, and a screen that exists here. Tests, samples and project
instructions use The Office (US) only, and nothing from Friends.

**Independent Test**: follow the instructions word for word on a freshly seeded instance and get a
correct capture. Search for Friends references and find none.

**Acceptance Scenarios**:

1. **Given** the instructions, **When** an agent copies the example, **Then** the address is the
   seeded one and the screen exists.
2. **Given** tests, samples and instructions, **When** searched for Friends, **Then** none is found.
### Edge Cases

- The path lacks a leading slash, or is a full address on another site: the link lands on the root.
- The path does not exist: the person is signed in and sees the usual not found screen.
- Somebody is already signed in: the link replaces that session.
- The account is deleted between minting and opening: the link signs nobody in.
- The account uses two factor authentication: the link skips it, since it only works on a
  development instance minted by somebody who can already run the application.
- The address differs in letter case from the stored one: it matches, as the regular sign in does.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A command MUST take an account's email address and an optional path, and print one link.
- **FR-002**: The path MUST default to the root of the application.
- **FR-003**: A valid link MUST sign the browser in as the account and land on the path, inside the application.
- **FR-004**: A link MUST expire after a configurable lifetime, five minutes by default, which the command prints.
- **FR-005**: A link MUST be refused when any part of it was altered.
- **FR-006**: The feature MUST stay off unless a setting switches it on.
- **FR-007**: In production, the command MUST refuse and the link MUST answer as if it did not exist, whatever the setting says.
- **FR-008**: The command and the link MUST refuse a host other than `localhost`, a loopback address or a `.test` name.
- **FR-009**: The command MUST refuse an address no account uses.
- **FR-010**: Every refusal MUST exit with a failure, print no link, and say in one sentence what is wrong.
- **FR-011**: The screenshot instructions MUST describe the command with the seeded account, this project's host and a real screen.
- **FR-012**: Tests, samples and project instructions MUST use The Office (US) only, and nothing from Friends.
- **FR-013**: Automated tests MUST cover the command, the link, every refusal, and the expired and altered cases.

## Success Criteria *(mandatory)*

- **SC-001**: A screen behind the sign in is captured with two commands, in under one minute.
- **SC-002**: 100% of attempts outside a switched on development instance are refused.
- **SC-003**: 100% of expired or altered links sign nobody in.
- **SC-004**: An agent following the instructions on a freshly seeded instance captures correctly on the first attempt.
- **SC-005**: A search for Friends references in tests, samples and instructions returns nothing.

## Assumptions

- The seeded account is `michael.scott@dundermifflin.com`, as the configuration already says. The
  instructions currently say `@dundermifflin.example`, `erica.test` and a screen from another
  project, and are corrected. The project is served locally on `http://jacques.test`.
- The existing local sign in shortcut stays as it is.
- Tests and seed data hold no Friends reference today. Mentions of "Monica" naming the Monica
  product are not references to the TV show and stay. `.claude/worktrees` is out of scope.
