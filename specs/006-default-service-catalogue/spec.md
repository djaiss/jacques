# Feature Specification: Default Service Catalogue

**Feature Branch**: `2026-10-14-default-service-catalogue` | **Created**: 2026-10-14 | **Status**: Draft

**Input**: User description: "A job `CreateDefaultServices` fills each new company's catalogue with
real, editable, translated records, offered at its first location." The request's full list is the
Default catalogue table below.

## Clarifications

### Session 2026-10-14

- Q: How is a step that needs no staff stored? → A: As the processing part of the step before it; no schema change, 6 steps in all.
- Q: Which prices without a currency? → A: The CAD amounts, in cents, for every company whatever its currency.
- Q: Which languages are the defaults written in? → A: The 4 interface locales: English, French, German, Spanish.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new salon starts with a realistic catalogue (Priority: P1)

The owner signs up. By the time they reach step 2 of the onboarding (004), the catalogue already
holds the 6 categories and 17 services below, in order, active, and editable like any other record.

**Independent Test**: sign up, open the catalogue, and count 6 categories and 17 services in order.

**Acceptance Scenarios**:

1. **Given** a company just created, **When** the owner reaches step 2, **Then** the 6 categories and 17 services exist, in the order of the table, all services active.
2. **Given** the default catalogue, **When** the owner renames "Blowout" or changes its price, **Then** it is saved like any service they created.
3. **Given** "Full Color", **When** the owner opens it, **Then** its steps total 90 minutes, with 30 minutes during which the team member is free.
4. **Given** two companies created at the same time, **When** each opens its catalogue, **Then** each sees only its own 17 services.

### User Story 2 - The default texts speak the salon's languages (Priority: P1)

The owner picks English and French at step 1 (005). At step 2, "Women's Haircut" also reads
"Coupe femme", without the owner typing anything.

**Independent Test**: sign up, choose French and Spanish at step 1, and check every default category and service has its name in both.

**Acceptance Scenarios**:

1. **Given** a salon in English and French, **When** the owner reaches step 2, **Then** every default category and service shows its name in both languages.
2. **Given** a salon that adds German later, **When** the owner opens a default service, **Then** its German name is already there.
3. **Given** a salon in English and Portuguese, **When** the owner reaches step 2, **Then** the Portuguese names are empty and must be typed before a default is saved again (005, FR-010).

### User Story 3 - The first location offers the default services (Priority: P1)

Once step 1 creates the first location, it offers all 17 services at the company price.

**Independent Test**: complete step 1, then check the first location offers 17 active services with no price override.

**Acceptance Scenarios**:

1. **Given** step 1 just submitted, **When** somebody looks at what the first location offers, **Then** the 17 services are there, active, at the company price.
2. **Given** step 1 submitted a second time, **When** the location is updated, **Then** no service is offered twice.
3. **Given** a second location created later, **When** somebody looks at what it offers, **Then** no default service is assigned to it.

### Edge Cases

- The work fails halfway: nothing of the default catalogue is kept, and the failure is logged.
- The work runs again for one company: nothing is duplicated, and edits and deletions stay.
- The owner deletes a default service before step 1: the first location offers the remaining ones only.
- Step 1 is submitted before the default catalogue exists: the first location still ends up offering it.
- A company existing before this feature gets nothing.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Creating a company MUST start the creation of its default catalogue once the company is saved, in the background, given the company and nothing else.
- **FR-002**: The default catalogue MUST be the categories, services, prices and steps of the Default catalogue table, in its order, every service active.
- **FR-003**: The default records MUST be ordinary categories, services and steps, editable and deletable like any other.
- **FR-004**: A company MUST record that it received the default catalogue, and receive it at most once; a repeated run MUST create, change and restore nothing.
- **FR-005**: The default catalogue MUST be created entirely or not at all, and a failure MUST be logged the way the application logs a failed background task.
- **FR-006**: A service the table gives no steps MUST be stored as one step of its whole duration, since a service without a step is refused (003, FR-004).
- **FR-007**: A stretch the request marks as not requiring staff MUST be stored as the processing duration of the step before it, as the step table shows.
- **FR-008**: Each step layout MUST total the duration of its service.
- **FR-009**: Default prices MUST be the amounts of the Default catalogue table in cents, written as the same number in every offered currency (007, FR-015).
- **FR-010**: Every default category and service name MUST be stored in English, French, German and Spanish, whatever the salon languages are at the time.
- **FR-011**: The default names MUST be written by the product team in each language, never machine translated at runtime.
- **FR-012**: When the first location of a company is created, and only then, every default service still existing MUST be offered there once, active, with no price override.
- **FR-013**: Every record created MUST belong to that company, and only its own first location MUST receive the offers.

### Key Entities

- **Default catalogue**: the fixed list below, owned by the product, copied into each new company.
- **Service category, Service, Service step, Location service** (003): receive the copy as ordinary records.

### Default catalogue

| # | Category | Services (minutes, CAD) |
| --- | --- | --- |
| 1 | Haircuts | Men's Haircut 30, 35 · Women's Haircut 60, 65 · Kids' Haircut 30, 25 |
| 2 | Hair Coloring | Full Color 90, 100 · Root Touch-up 90, 85 · Balayage 150, 180 · Highlights 105, 140 |
| 3 | Hair Styling | Blowout 45, 45 · Updo 60, 75 · Hair Straightening 90, 100 |
| 4 | Hair Treatments | Deep Conditioning 35, 40 · Keratin Treatment 120, 150 · Scalp Treatment 45, 55 |
| 5 | Barbering | Beard Trim 20, 20 · Traditional Shave 30, 35 · Hair & Beard 50, 50 |
| 6 | Other Services | Consultation 15, 0 |

| Service | Step 1 (active + processing) | Step 2 (active) |
| --- | --- | --- |
| Full Color | 30 + 30 | 30 |
| Highlights | 45 + 30 | 30 |
| Deep Conditioning | 10 + 15 | 10 |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of companies created after the release have the 6 categories and 17 services before their owner reaches step 2.
- **SC-002**: 0 duplicated default records after the work runs any number of times.
- **SC-003**: 0 deleted default services reappear.
- **SC-004**: 100% of first locations offer every remaining default service right after step 1.

## Assumptions

- The first location and the salon languages come from step 1 (004, 005), after signup, so the catalogue is created at signup and offered at the location when step 1 creates it.
- Categories have no active state (003); "active" applies to services and location offers.
- Step names in the request are illustrative: steps hold no text (005), so only their durations are kept.
- No API or webhook change: the records are created by the same operations the API already uses.
