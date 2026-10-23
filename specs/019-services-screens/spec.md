# Feature Specification: Services screens

**Feature Branch**: `2026-10-23-services-screens` | **Created**: 2026-10-23 | **Status**: Draft

## Clarifications (2026-10-23)

- Q: Which languages does the form edit? → A: Every language of the salon, with a language switch as in onboarding.
- Q: The detail statistics need appointments, which do not exist. → A: Draw them with made up figures, so the blocks exist and are visibly waiting for appointments.

**Input**: User description: "Ajoute l'écran pour gérer les services, accessible depuis le top menu, dans un écran dédié: listing des services, ajout d'un service et d'une catégorie, détail du service, édition du service. Sois rigoureux." Design: `Services.dc.html` (claude.ai/design project 05aec2c1). The API was dropped from the request.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the catalogue of the salon (Priority: P1)

The Services tile of the top menu opens the list. It shows a lead sentence, a search field, "Add a category" and "Add a service", a side navigation (SALONS: "All salons" then one entry per location with its number of services and people; CATEGORIES: one anchor per category with its count), and one card per category. Each row shows the name, a "N STEPS" badge when the service has more than one step, the duration, one price per currency of the salon, the salons offering it ("All 3 salons", "2 salons", or "Not offered anywhere yet" in warning), and the people doing it (avatars, "+N" beyond the visible ones, or "Nobody does it yet" in warning). Picking a salon filters people and greys services not offered there ("Not offered at Stamford").

**Why this priority**: every other story starts here, and the catalogue cannot be read anywhere today outside onboarding.

**Independent Test**: with 17 services, 6 categories and 3 salons, search "cut", pick Stamford, check rows and warnings.

**Acceptance Scenarios**:

1. **Given** a person allowed to see services, **When** they follow Services in the top menu, **Then** the list shows, and the tile no longer leads nowhere.
2. **Given** the list, **When** they type "balay", **Then** only matching services show, categories without a match disappear, and with no match at all the card "No service is called that. Check the spelling, or add it as a new service." shows.
3. **Given** the list, **When** they pick Stamford, **Then** the lead reads "N of M services are offered at Stamford, by P people.", the people column heading reads "PEOPLE AT STAMFORD", and the picked salon has its own address, so going back keeps it.
4. **Given** a person who may not see services, **When** they open the list address, **Then** they get a not found page and the tile leads nowhere.
5. **Given** a company with no category and no active service, **When** the list opens, **Then** it reads "Nothing for clients to book yet." over the card "No services yet" with "Add a service", and "Start from a ready-made catalog" when the catalogue holds nothing at all, archived services included; search and navigation are hidden.

### User Story 2 - Add a service and a category (Priority: P1)

"Add a service" (top of the list, or the "+ Add a service" of a category, which preselects it) opens its own page: eyebrow "ADD A SERVICE", Cancel and "Add this service". Sections: Details (name, category, steps and duration, one price per currency, optional description) and "Where it is offered, and by whom" (one box per salon: tick the salon, then the people working there; "Tick everyone at Scranton" or "Untick everyone"; "Nobody yet" in warning). "Add a category" opens a page asking for its name. Saving either goes back to where it belongs with a confirmation.

**Why this priority**: a salon grows its catalogue after onboarding, and the list has no value if it cannot change.

**Independent Test**: add "Beard Trim" in Haircuts, 20 min, CA$ 15 and US$ 12, offered at Scranton by Jim; then add the category "Barbering".

**Acceptance Scenarios**:

1. **Given** the add page, **When** the owner saves with a name, a duration and a price, **Then** the service exists with those values and its page shows.
2. **Given** the add page, **When** the name is empty or a duration is not between 1 and 1440 minutes, **Then** nothing is saved and the field says why.
3. **Given** a salon ticked with Jim, **When** the service is saved, **Then** it is offered at that salon and only Jim can be booked for it there.
4. **Given** the category page, **When** the owner saves "Barbering", **Then** it appears last in the list and in the navigation, empty.

### User Story 3 - Read one service (Priority: P2)

A row opens the service page: breadcrumb, category eyebrow, name, steps badge, "Edit this service", the four statistics, "Bookings per week", "What stands out", then About (description, or "No description yet. Clients only see the name when they book it."; facts: duration, price, offered at, done by) and "Who does it" (one box per salon: "Not offered here.", "Offered here, but nobody does it yet, so it cannot be booked." in warning, or its people).

**Acceptance Scenarios**:

1. **Given** Balayage offered at Scranton by Pam and at Stamford by nobody, **When** the page opens, **Then** Scranton lists Pam, Stamford shows the warning and Nashua "Not offered here.".
2. **Given** a service of another company, **When** its address is opened, **Then** a not found page shows.

### User Story 4 - Edit, archive and restore a service (Priority: P2)

"Edit this service" opens the same form as adding, titled "EDIT SERVICE" with "Save changes", plus an Archive section: "Archive this service", then the confirmation "The service leaves the catalog and nobody can book it. Sure?" with "Keep it" and "Archive it". An archived service leaves the list and can be restored from its page.

**Acceptance Scenarios**:

1. **Given** the edit page, **When** the owner unticks Stamford and saves, **Then** the service is no longer offered there and its people there are released from it.
2. **Given** the archive confirmation, **When** they press "Archive it", **Then** the service leaves the list and the booking offer, and its past records stay.
3. **Given** an archived service, **When** the owner restores it, **Then** it comes back as it was.
4. **Given** a person who may see but not change services, **When** they open a service, **Then** no edit, add or archive control shows and their addresses return not found.

### User Story 5 - Rename and remove a category (Priority: P3)

A category card's name opens its edit page: rename it, or remove it after a warning that says what happens to its services.

**Acceptance Scenarios**:

1. **Given** Makeup with two services, **When** the owner removes it, **Then** a warning names the two services, and on confirmation they move to "Without a category".

### Edge Cases

- A salon with several languages: the form switches language for the name, the description and the step names; the list and the service page read the language of the person, falling back to the salon's first language.
- A service with three steps: the duration shown is the sum of its steps, and the form keeps at least one step that needs a team member.
- A price left empty in one currency: the row shows "Add a price" in warning for that currency; 0 is a valid price.
- Someone working at two salons appears in both boxes, ticked separately.
- An archived person or salon never appears in the form; a paused assignment shows unticked.
- The list works on a phone (navigation above the cards, duration under the name), with the keyboard, a screen reader and in dark mode.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The Services tile MUST open the list for a person allowed to see services, and lead nowhere otherwise.
- **FR-002**: Seeing the screens MUST require the permission to see services; adding, editing, archiving, restoring and every category change MUST require the permission to change them.
- **FR-003**: Each of list, service, add service, edit service, add category and edit category MUST have its own address.
- **FR-004**: The list MUST show the columns, warnings, search, salon filter and navigation of US1, with archived services left out.
- **FR-005**: The service form MUST edit name, description, category, steps (name, duration, needs a team member, order), one price per currency of the salon, the salons offering it and the people doing it in each.
- **FR-006**: Only people working at a salon MUST be offered for that salon.
- **FR-007**: Archiving MUST stop the service being booked anywhere without deleting it, and restoring MUST bring it back unchanged.
- **FR-008**: Removing a category MUST warn first and keep its services, without a category.
- **FR-009**: Every change MUST be recorded in the activity log like the other catalogue changes.
- **FR-010**: Every string MUST be translated in every configured locale; counts MUST vary with their number.
- **FR-011**: The service page MUST draw the statistics of the design (bookings, earned, earned per booked hour, clients who came back, bookings per week over 12 weeks, "What stands out", bookings per salon and per person) from made up figures, stable for a given service, until appointments exist.
- **FR-012**: Those figures MUST carry a visible note saying they are sample figures, so nobody reads them as real.

### Key Entities

- **Service**: per-language name and description, category, ordered steps, one price per currency, active or archived.
- **Category**: per-language name, position.
- **Offer**: a service offered at one salon, with the people who do it there.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: From any screen, a service is reached in three actions or fewer: menu, Services, row.
- **SC-002**: Adding a service with one salon and one person takes one page and one save.
- **SC-003**: The list of 17 services, 6 categories and 3 salons reads without horizontal scroll at 375, 768 and 1280 pixels.
- **SC-004**: No screen lets a person without the right permission see or change what it would refuse.
- **SC-005**: Side by side with the design at the same width, in light and dark mode, no visible difference in layout or wording remains.

## Assumptions

- Prices are the salon prices per currency; the per-salon price override (D8) keeps working but is not edited here.
- Ordering services and categories by hand stays an onboarding feature, and onboarding keeps its own catalogue screen.
- Archiving reuses the existing "stop offering a service anywhere"; the list does not show archived services, the service page does.
- The made up figures are replaced by real ones in the feature that brings appointments; until then they are produced in one place, easy to find and remove.
