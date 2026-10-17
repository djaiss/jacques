# Feature Specification: Settings Browser Tests

**Feature Branch**: `2026-10-16-browser-testing`

**Created**: 2026-10-16

**Status**: Draft

**Input**: User description: "add browser tests for all the features under http://jacques.test/settings in the app."

## User Scenarios & Testing *(mandatory)*

The users are the contributors to Jacques. Each story is one area of the settings, driven in a real browser the way a salon employee uses it, so a broken script, a missing confirmation or a dead button fails the suite.

### User Story 1 - Settings home and navigation (Priority: P1)

A signed in person opens the settings, sees their name and role, and reaches every area listed for them.

**Why this priority**: every other story starts from this screen.

**Independent Test**: sign in, open the settings, follow each row and come back through the breadcrumb.

**Acceptance Scenarios**:

1. **Given** a person allowed to manage locations and roles, **When** they open the settings, **Then** they see the "You" and "This company" sections and each row leads to its screen.
2. **Given** a person with neither permission, **When** they open the settings, **Then** the "This company" section is absent and the administration screens refuse them.

### User Story 2 - Own account: profile, preferences, logs (Priority: P1)

A person edits their name and email, uploads and removes their photo, changes language and time format, and reads their activity and the emails sent to them.

**Why this priority**: every employee uses these screens, whatever their role.

**Independent Test**: each screen is tested alone from a freshly signed in person.

**Acceptance Scenarios**:

1. **Given** the profile screen, **When** the person saves a new name, **Then** the screen shows it and invalid input shows an error without losing what was typed.
2. **Given** the profile screen, **When** the person uploads a photo then removes it, **Then** the avatar changes, and the removal asks for confirmation first.
3. **Given** the preferences screen, **When** the person picks another language or time format, **Then** the interface and the time preview change accordingly.
4. **Given** recorded activity and sent emails, **When** the person opens the logs, **Then** they see them, can load more when there are more, and see the empty state when there are none.

### User Story 3 - Own account: security (Priority: P1)

A person changes their password, turns two factor authentication on and off, regenerates recovery codes, and creates and revokes API keys.

**Why this priority**: a regression here locks people out or leaves an account exposed.

**Independent Test**: each operation is tested alone from a freshly signed in person.

**Acceptance Scenarios**:

1. **Given** the security screen, **When** the person changes their password with the right current one, **Then** they are told it changed; with a wrong one, they see an error.
2. **Given** two factor authentication is off, **When** the person enables it with a valid code, **Then** it shows as on with recovery codes; an invalid code is refused.
3. **Given** two factor authentication is on, **When** the person regenerates recovery codes or disables it, **Then** the screen reflects it, and disabling asks for confirmation first.
4. **Given** the API keys section, **When** the person creates a key, **Then** its token is shown once; **When** they revoke it, **Then** they confirm and it disappears.

### User Story 4 - Administration: roles and permissions (Priority: P2)

An administrator creates, renames, duplicates and deletes a role, changes its permissions and their scope, and assigns and removes people.

**Why this priority**: only administrators use it, but a mistake changes what everybody can do.

**Independent Test**: sign in as a person allowed to manage roles and run each operation alone.

**Acceptance Scenarios**:

1. **Given** the roles list, **When** the administrator creates or duplicates a role, **Then** it appears in the list and opens.
2. **Given** a role, **When** the administrator filters, grants and scopes permissions and saves, **Then** the choices persist after a reload; a filter matching nothing says so.
3. **Given** a role's people tab, **When** the administrator assigns then removes a person, **Then** the list follows, and removal asks for confirmation first.
4. **Given** a role, **When** the administrator deletes it, **Then** a confirmation comes first and the role leaves the list.

### User Story 5 - Administration: locations (Priority: P2)

An administrator creates and edits a location, archives and restores it, filters and searches the list, and assigns and removes people.

**Why this priority**: same audience and risk as roles; tested the same way, as a person allowed to manage locations.

**Acceptance Scenarios**:

1. **Given** the locations list, **When** the administrator creates or edits a location, **Then** the list shows the change; invalid input shows an error.
2. **Given** an active location, **When** the administrator archives it, **Then** a confirmation comes first and it moves to the archived filter, from where it can be restored.
3. **Given** several locations, **When** the administrator searches by name, city or country, **Then** only the matching ones remain.
4. **Given** a location, **When** the administrator assigns then removes a person, **Then** the list follows, and removal asks for confirmation first.

### Edge Cases

- A destructive control (delete, revoke, archive, unassign, disable, remove photo) cancelled in its confirmation changes nothing.
- A person from another company never appears in a list and cannot be assigned.
- A page that updates in place (load more, filters, saves) raises no browser script error.
- A screen is usable at phone width for at least the settings home and one form per story.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every screen under the settings MUST be covered by at least one browser test that opens it and checks it renders without a script error.
- **FR-002**: Every operation a settings screen offers MUST have a browser test of its successful path.
- **FR-003**: Every form MUST have a browser test of at least one validation failure.
- **FR-004**: Every destructive operation MUST have a browser test proving it asks for confirmation, and that cancelling changes nothing.
- **FR-005**: The administration screens MUST have a browser test proving a person without the matching permission neither sees nor reaches them.
- **FR-006**: The browser tests MUST follow the existing browser tests and the project testing rules, one file per screen, grouped like the settings themselves (account, administration).
- **FR-007**: The browser tests MUST run with the rest of the suite, locally and in continuous integration, without a manually started server.
- **FR-008**: Production code MUST NOT change, except where a test reveals a defect, which the pull request then names.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of the settings screens (11) and of the operations they offer have at least one passing browser test.
- **SC-002**: Breaking any confirmation dialog, form submission or in place update in the settings makes at least one browser test fail.
- **SC-003**: The new browser tests add less than 3 minutes to a full parallel run of the suite.
- **SC-004**: The suite passes three consecutive runs with no intermittent failure in the new tests.

## Assumptions

- Scope is the 11 screens of the settings as of this date: home, profile, preferences, logs, emails sent, security, two factor setup, roles list, role permissions, role people, locations. Screens added later are out of scope.
- The browser tooling already used by the registration and sign in browser tests is reused; nothing new is installed.
- Existing feature tests stay; browser tests check what only a browser shows (scripts, confirmations, in place updates), not every server rule again.
- A valid two factor code is computed inside the test from the secret the screen shows.
