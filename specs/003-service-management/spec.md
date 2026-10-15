# Feature Specification: Service Management

**Feature Branch**: `2026-10-14-service-management` | **Created**: 2026-10-14 | **Status**: Draft

**Input**: User description: "Add the notion of services for a salon", offered at locations.

Introduces the catalogue of `docs/specs/decisions.md` (D5, D8, Catalogue). D8 was rewritten for it.

## Clarifications

### Session 2026-10-14

- Q: A single duration, or steps as D5 requires? → A: Steps now; the duration is their total.
- Q: What overrides the company values? → A: The location overrides the price only; no duration override anywhere.
- Q: How far does a Manager reach? → A: What their locations offer; only the Owner edits the catalogue.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The salon builds its catalogue (Priority: P1)

An Owner creates categories and services for the whole company, describes each service as a
sequence of steps, orders them, edits them, and deactivates a service the salon no longer offers.

**Why this priority**: nothing can be offered, booked or priced without a catalogue.

**Independent Test**: create two categories and three services, reorder them, deactivate one.

**Acceptance Scenarios**:

1. **Given** an Owner, **When** they create "Colour" (35.00) in "Colours" with steps of 20 active minutes, then 30 minutes of processing, then 15 active minutes, **Then** it lasts 65 minutes and appears last in its category.
2. **Given** a service in a category, **When** the category is deleted, **Then** the service remains, without a category.
3. **Given** an active service, **When** it is deactivated, **Then** it is no longer offered anywhere and its history stays intact.
4. **Given** a Manager, a Staff member or a Receptionist, **When** they try to change the catalogue, **Then** it is refused.

### User Story 2 - Each location offers its own selection (Priority: P1)

An Owner, or a Manager of the location, chooses which services the location offers, and may set a
different price there. An empty override means the company price applies.

**Why this priority**: a service is available at a location only once assigned there.

**Independent Test**: assign "Haircut" to location A with a price of 40.00 and to B with no override.

**Acceptance Scenarios**:

1. **Given** "Haircut" at 35.00, **When** location A overrides the price with 40.00, **Then** A shows 40.00 and B shows 35.00.
2. **Given** an override at A, **When** the company price changes, **Then** A keeps its override and B follows the new price.
3. **Given** a service never assigned to A, **When** somebody looks at what A offers, **Then** the service is absent.
4. **Given** a service assigned to A, **When** it is deactivated at A only, **Then** A stops offering it and B is unaffected.
5. **Given** a Manager assigned to A only, **When** they change what B offers, **Then** it is refused.

### User Story 3 - Team members perform services at locations (Priority: P2)

An Owner, or a Manager of the location, declares which services each team member performs at each
location they are assigned to.

**Why this priority**: availability needs it, but the catalogue is useful before it.

**Independent Test**: let Jim perform "Haircut" at A, then try the same at B where Jim is not assigned.

**Acceptance Scenarios**:

1. **Given** Jim assigned to A and "Haircut" offered at A, **When** Jim is set to perform it at A, **Then** it succeeds.
2. **Given** Jim not assigned to B, **When** Jim is set to perform a service at B, **Then** it is refused.
3. **Given** a service not offered at A, **When** somebody is set to perform it at A, **Then** it is refused.

### Edge Cases

- A service, category, location or team member of another company is treated as if it did not exist.
- Removing a team member, or a service, from a location removes who performs what there.
- A deactivated service cannot be newly assigned to a location or a team member.
- A service without a step is refused; durations are whole minutes, a price is zero or more.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Authorized people MUST be able to create, edit, reorder and delete service categories of their company.
- **FR-002**: A category MUST have a name, MAY have a description, and MUST have a display position.
- **FR-003**: Authorized people MUST be able to create, edit, reorder, deactivate, reactivate and delete services of their company.
- **FR-004**: A service MUST have a name, a price and at least one step, and MAY have a description and a category.
- **FR-005**: A step MUST have an active duration above zero, a processing duration of zero or more, and a rank in its service (D5).
- **FR-006**: The duration of a service MUST be the sum of the active and processing durations of its steps.
- **FR-007**: Prices MUST be stored in the smallest unit of the company currency.
- **FR-008**: Services MUST be ordered within their category, and categories within the company.
- **FR-009**: A service MUST be available at a location only after being explicitly assigned to it.
- **FR-010**: A location assignment MUST carry an active state and MAY override the price; nothing overrides the duration (D8).
- **FR-011**: An empty price override MUST resolve to the current company price of the service.
- **FR-012**: A team member MUST be able to perform a service at a location only when assigned to that location and the service is offered there.
- **FR-013**: Every association MUST join records of the same company, and any other attempt MUST fail as if the record did not exist.
- **FR-014**: Deleting a service used by past appointments MUST be refused; deactivation is offered instead.
- **FR-015**: Only an Owner MUST be able to change categories, services and steps.
- **FR-016**: An Owner, or a Manager on their assigned locations, MUST be able to manage location assignments, price overrides and who performs what.
- **FR-017**: Staff and Receptionist MUST be able to read the catalogue and change nothing (default roles matrix, 001).
- **FR-018**: When appointments are introduced, each MUST store the name, steps, duration and price of its services as they were at booking.
- **FR-019**: Every create, update and delete MUST be available to the back office and to the public API through the same operations.

### Key Entities

- **Service category**: a named, ordered group of services of one company.
- **Service**: something the salon sells, defined once for the company, with a default price.
- **Service step**: one ranked part of a service, with active and processing durations.
- **Location service**: a service offered at one location, with an active state and an optional price.
- **Team member service**: a team member performing a location service at that location.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An Owner creates a three step service and offers it at a location in under two minutes.
- **SC-002**: 100% of cross company association attempts are refused, each backed by an automated check.
- **SC-003**: Deactivating a service leaves every past record that mentions it unchanged.
- **SC-004**: The price at a location always equals its override, or the company price without one.

## Assumptions

- "Staff" in the description means any team member (D3), whatever their role.
- Appointments do not exist yet; FR-014 and FR-018 are enforced by the feature that introduces them.
- Prices are shown in the company currency (D2); Jacques never charges them (D22).
- Scope is the data, rules and operations; the back office screens follow in a later feature.
