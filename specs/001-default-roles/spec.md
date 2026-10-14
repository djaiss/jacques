# Feature Specification: Default Roles

**Feature Branch**: `2026-10-14-default-roles` | **Created**: 2026-10-14 | **Status**: Draft

**Input**: User description: "By default, an account has these four roles: Owner, Manager, Staff,
Receptionist. Change the current code to support them. The old permissions are wrong."

Product decisions D1 and D4 were rewritten for this feature (`docs/specs/decisions.md`).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Every company starts with the four roles (Priority: P1)

A salon signs up. Its company holds Owner, Manager, Staff and Receptionist. The person who signed
up holds Owner. The old default roles (Administrator, People administrator, IT administrator,
Member) and the old permissions no longer exist.

**Why this priority**: every other story depends on these four roles existing.

**Independent Test**: create a company, then list its roles and the roles of its creator.

**Acceptance Scenarios**:

1. **Given** a new sign up, **When** the company is created, **Then** it holds the four roles and its creator holds Owner.
2. **Given** an Owner, **When** they open the role editor, **Then** they can change Manager, Staff and Receptionist, and create or delete other roles, as today.
3. **Given** an Owner, **When** they try to change or delete Owner, **Then** it is refused.

### User Story 2 - Each role grants exactly what it describes (Priority: P1)

A person sees and changes only what their roles allow, following the matrix in FR-004.

**Why this priority**: wrong permissions expose data or block a salon's daily work.

**Independent Test**: for each role, attempt every capability of the matrix and compare with it.

**Acceptance Scenarios**:

1. **Given** a Manager, **When** they open the Jacques subscription billing, **Then** it is refused as if it did not exist.
2. **Given** a Manager assigned to location A only, **When** they change the opening hours of location B, **Then** it is refused.
3. **Given** a Staff member, **When** they open the agenda of a colleague, **Then** it is refused.
4. **Given** a Receptionist assigned to location A, **When** they create an appointment for any team member of A, **Then** it succeeds.
5. **Given** a person holding Staff and Receptionist, **When** they act, **Then** they may do what either role allows.

### User Story 3 - The Owner hands out roles and locations (Priority: P2)

The Owner gives or takes away any role, including Owner, and assigns each person to one or more
locations.

**Why this priority**: a salon needs other administrators, but can run for a while with one.

**Independent Test**: as Owner, give Manager to a colleague on one location, then sign in as them.

**Acceptance Scenarios**:

1. **Given** an Owner, **When** they give Owner to a colleague, **Then** that colleague has full access.
2. **Given** an Owner, **When** they assign a Manager to locations A and B, **Then** the Manager acts on A and B only.
3. **Given** a Manager, **When** they try to give or take away a role, **Then** it is refused.
4. **Given** a company with a single Owner, **When** somebody takes Owner away from them, **Then** it is refused.

### Edge Cases

- A person holding no role, or assigned to no location, sees their own profile only.
- The last Owner of a company cannot lose the role, be deactivated or delete their own account.
- An archived location keeps its assignments, and nobody but an Owner acts on it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every new company MUST start with four roles: Owner, Manager, Staff, Receptionist.
- **FR-002**: The role editor MUST stay; Manager, Staff and Receptionist MUST be editable, Owner MUST NOT.
- **FR-003**: A person MAY hold several roles and MUST receive the union of what they grant.
- **FR-004**: Each default role MUST grant the following, and nothing more.

| Capability | Owner | Manager | Staff | Receptionist |
| --- | --- | --- | --- | --- |
| Jacques subscription billing | Yes | No | No | No |
| Company settings | Yes | No | No | No |
| Locations, opening hours | Yes | Assigned locations | No | No |
| Team schedules | Yes | Assigned locations | Own, read only | Assigned locations, read only |
| Team members | Yes | Assigned locations | Own profile | No |
| Services | Yes | Assigned locations | Read only | Read only |
| Clients | Yes | Yes | Clients of own appointments | Yes |
| Appointments | Yes | Assigned locations | Own appointments | Assigned locations |
| Reports | Yes | Assigned locations | No | No |
| Roles and location assignment | Yes | No | No | No |

- **FR-005**: A person MUST be assignable to one or more locations of their company.
- **FR-006**: No role MUST grant finances or payments, since Jacques never handles money (D22).
- **FR-007**: The editor MUST offer the capabilities of FR-004, each on a scope: themselves, assigned locations, or the whole company.
- **FR-008**: A company MUST always keep at least one active Owner.
- **FR-009**: Default role names and descriptions MUST be shown in the language of the reader.
- **FR-010**: A capability whose subject does not exist yet (services, clients, appointments, schedules, reports) MUST be declared now and enforced by the feature that introduces the subject.
- **FR-011**: Existing data MUST be converted: the company owner gets Owner; holders of Administrator get Manager on every location; every other person gets Staff.

### Key Entities

- **Role**: a named set of capabilities in a company; four are created by default, Owner is locked.
- **Role assignment**: links a person to a role; a person holds zero or more.
- **Location assignment**: links a person to a location of their company; a person holds zero or more.
- **Capability**: one thing a person may do, on a scope (themselves, assigned locations, the company).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of new companies hold the four default roles right after sign up.
- **SC-002**: Every cell of the FR-004 matrix is backed by an automated check, allowed and refused.
- **SC-003**: No company can reach a state with zero active Owner.
- **SC-004**: An Owner designates a second Owner, or assigns a Manager to a location, in under one minute.

## Assumptions

- The tenant called "account" by the user is the company in the product.
- Owner replaces the special "company owner" status: the creator holds Owner like any other role.
- "Authorized agendas" of the Receptionist means the agendas of their assigned locations.
- No production data exists yet, so FR-011 only has to cover development and staging data.
- Deactivating a person (D3) keeps their roles and locations but blocks their access.
