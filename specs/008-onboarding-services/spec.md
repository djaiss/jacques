# Feature Specification: Onboarding Step 2, Services

**Feature Branch**: `2026-10-15-onboarding-services-step` | **Created**: 2026-10-15 | **Status**: Clarified | **Input**: "Add step 2 of the onboarding process: services management", design `Onboarding Services.dc.html`.

## Clarifications (2026-10-15)

- Q: Draft or live catalogue? → A: Live. Every change is stored in the real catalogue at once; "I'll set this up later" leaves it as it is.
- Q: May the owner continue with names, translations or prices missing? → A: Yes, flagged. This amends spec 005 FR-010 and spec 007.
- Q: How are steps stored? → A: As drawn: a name per salon language, a duration, and whether it needs a team member. This amends D5 and spec 003 FR-005.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The owner adjusts the example catalogue (Priority: P1)

Michael reaches "Your services" with the example catalogue created at sign up. He edits durations
and prices in the list, removes what Dunder Mifflin Cuts does not offer, and continues.
**Why this priority**: it is the reason the step exists; today step 2 is a placeholder.
**Independent Test**: edit a duration and a price inline, remove a service, reload, continue.
**Acceptance Scenarios**:

1. **Given** a new salon, **When** step 2 opens, **Then** each category lists its services with name, duration and one price field per salon currency.
2. **Given** the list, **When** a duration or a price changes, **Then** it is stored with no other action and the footer says "Saved".
3. **Given** "Root Touch-up", **When** it is removed, **Then** the footer offers "Undo", which puts it back in place.
4. **Given** step 2, **When** "Continue to your team" is chosen, **Then** step 3 opens.

### User Story 2 - The owner organizes categories (Priority: P1)

**Why this priority**: categories are how clients browse; the example ones rarely fit exactly.
**Independent Test**: rename "Hair Styling", add "Nails", move "Barbering" up, remove "Hair Treatments".
**Acceptance Scenarios**:

1. **Given** a category, **When** its name is edited in place, **Then** it is stored; the first cannot move up, the last cannot move down.
2. **Given** "Add a category", **When** chosen, **Then** an unnamed, expanded category appears last with "Add a service".
3. **Given** a category of three services, **When** removed, **Then** the owner is warned the three go with it, and can undo afterwards.

### User Story 3 - The owner edits a service and breaks it into steps (Priority: P1)

The owner opens "Full Color" in a side panel, edits its texts, category and prices, and describes
its steps: application, processing while the team member is free, rinse and finish.
**Why this priority**: steps with processing time are the central idea of the product (D5).
**Independent Test**: break "Balayage" into 45 min busy, 30 min free, 30 min busy; the list shows 105 min and "3 steps".
**Acceptance Scenarios**:

1. **Given** a one step service, **When** opened, **Then** the panel shows name, description, category, prices, one duration and "Break it into steps".
2. **Given** "Break it into steps", **When** chosen, **Then** a busy step with the current duration and a 15 minute free step "Processing" are created.
3. **Given** steps, **When** one changes, **Then** the panel shows appointment length, busy time, free time and a proportional timeline.
4. **Given** two steps, **When** one is removed, **Then** the service returns to a single duration equal to the other.
5. **Given** a service with steps, **When** listed, **Then** its duration is their sum, read only, with a "3 steps" badge.

### User Story 4 - The owner fills in every salon language (Priority: P2)

**Why this priority**: clients see the catalogue in the language they book in.
**Independent Test**: with English and French, add a service named in English only, pick French, name it, see "All translated".
**Acceptance Scenarios**:

1. **Given** several languages, **When** the step opens, **Then** each language card shows flag, progress and missing count; the first says "Default".
2. **Given** French picked, **When** a French text is missing, **Then** the default text shows muted, and its category and service are flagged with a count.
3. **Given** a single language salon, **When** the step opens, **Then** no language card nor marker is shown.

### User Story 5 - The owner starts over, or postpones (Priority: P3)

**Why this priority**: a barber wants to start from nothing; a missing price list must not block onboarding.
**Independent Test**: empty the catalogue, undo, empty it again, restore the example, then choose "I'll set this up later".
**Acceptance Scenarios**:

1. **Given** a catalogue, **When** "Start with an empty catalog" is confirmed, **Then** the empty state shows, with undo.
2. **Given** an empty catalogue, **When** "Use the example catalog" is chosen, **Then** the example of spec 006 is created again.
3. **Given** step 2, **When** "I'll set this up later" is chosen, **Then** step 3 opens and the catalogue is unchanged; returning to step 2 later still allows editing.

### Edge Cases

- A service without a price in a currency shows "No CA$ price" (or "No price"), and the title counts them. 0 means free.
- A duration below one minute, or a value that is not a number, is refused and the field says why.
- A free step may come first or follow another, but a service keeps at least one busy step.
- A name may be empty ("Untitled", flagged); a written name stays unique per language in the company.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Step 2 MUST be reachable under the onboarding rules of spec 004, and show the breadcrumb, eyebrow, title, introduction and tracker of the design.
- **FR-002**: "I'll set this up later" and "Continue to your team" MUST both complete step 2 and open step 3, amending spec 004's "no skip".
- **FR-003**: Every change MUST be stored in the real catalogue as it is made, with no save action and no "Draft" badge.
- **FR-004**: The footer MUST say "Saved" for about two seconds after a change, and otherwise how many services exist, or that they can be added later from Settings.
- **FR-005**: Categories and services MAY lack names and prices; a service is bookable in a currency only with a price in it and a name in the first salon language.
- **FR-006**: Each category MUST show a tile with its service count, its name editable in place, and collapse, up, down and remove controls.
- **FR-007**: Removing a category holding services, or emptying the catalogue, MUST warn first and name what goes.
- **FR-008**: Each service row MUST show name, duration, a price field per currency in salon order, and edit and remove controls; only a one step service has an editable duration.
- **FR-009**: A price field MUST name its currency to assistive technology and accept that currency's decimals.
- **FR-010**: "Add a service" MUST create a 30 minute service with no price in that category and open it in the panel.
- **FR-011**: The side panel MUST edit name and description in the picked language, and category, prices and duration or steps.
- **FR-012**: Steps MUST be addable (15 min, busy), reorderable and removable, each with a name, a duration and "Needs a team member"; a side card explains them.
- **FR-013**: With several languages, cards MUST count category names, service names, written descriptions and multi step services' step names per language.
- **FR-014**: The picked language MUST apply to the list and the panel, starting with the first salon language.
- **FR-015**: "Use the example catalog" MUST recreate the spec 006 catalogue, even though it was created once already.
- **FR-016**: Every service MUST be offered at the first location once step 2 is completed.
- **FR-017**: After removing a service, a category or the catalogue, the footer MUST name what went and offer "Undo", restoring it in place.
- **FR-018**: Counts MUST use plural rules, amounts and durations MUST follow the interface language, and the step MUST meet WCAG 2.2 AA.

### Key Entities *(include if feature involves data)*

- **Category**: an ordered group of services, with a name per salon language.
- **Service**: name and optional description per language, a price per currency, a rank, one or more steps.
- **Step**: optional name per language, duration in minutes, whether a team member is busy, rank.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An owner adjusting only prices finishes step 2 in under 5 minutes, one interaction plus typing per price.
- **SC-002**: No change is lost when the owner reloads, leaves, or returns from step 3.
- **SC-003**: Every removal can be reversed in one interaction until the next change.
- **SC-004**: With two languages, the owner sees which one is incomplete within 5 seconds.

## Assumptions

- The design's "finished" card is a prototype of the transition; both footer actions open step 3.
- Removing a single service or step uses undo instead of a warning; multi record removals warn (principle V).
- No appointment exists during onboarding, so a removed service is deleted, not deactivated.
- Per location prices and who performs what belong to Settings and step 3.
