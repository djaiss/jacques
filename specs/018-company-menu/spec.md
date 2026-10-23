# Feature Specification: Company menu

**Feature Branch**: `2026-10-23-company-menu` | **Created**: 2026-10-23 | **Status**: Draft

**Input**: User description: "Change le top menu dans l'app par ce design là. Implémente le design, et mets les boutons même s'ils ne mènent pour le moment à rien.", with the design `Company Menu.dc.html` (claude.ai/design project 05aec2c1).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Somebody opens the menu and reaches a part of the salon (Priority: P1)

On any screen that has the top bar, the button showing the company name opens a dark panel. The panel shows, from top to bottom: the hint "Press G to load this menu from anywhere", a search field, four tiles (Dashboard, Clients, Services, Team) each with its own icon, tint and shortcut label (^1 to ^4), then a "YOURS" heading over four links on two columns (Profile, Logs, Security and access, Preferences).

**Why this priority**: the menu is how a person moves around the app. The current one still names parts of another product (People, Assets, Software, Licences, Reports).

**Independent Test**: open the dashboard, press the company button, check the four tiles and the four personal links, then follow the Dashboard tile.

**Acceptance Scenarios**:

1. **Given** any screen with the top bar, **When** the person presses the company button, **Then** the panel opens below it, and pressing it again closes it.
2. **Given** the open panel, **When** the person reads the tiles, **Then** they see Dashboard, Clients, Services and Team in that order, on one row on a wide screen.
3. **Given** the open panel, **When** the person follows Dashboard, **Then** the dashboard shows.
4. **Given** a person allowed to manage the team, **When** they follow Team, **Then** the team members screen shows.
5. **Given** the open panel, **When** the person follows a link under YOURS, **Then** the matching account screen shows.
6. **Given** the open panel, **When** the person presses Escape or clicks outside it, **Then** it closes.

---

### User Story 2 - Parts that do not exist yet are already in the menu (Priority: P2)

Clients and Services have no screen yet. Their tiles are drawn exactly like the others so the menu already has its final shape, but they lead nowhere.

**Why this priority**: asked explicitly, and it fixes the layout before the screens exist.

**Independent Test**: open the panel and check that Clients and Services are visible and that activating them does not change the screen.

**Acceptance Scenarios**:

1. **Given** the open panel, **When** the person activates Clients or Services, **Then** the screen stays where it is and no error shows.
2. **Given** a person who may not manage the team, **When** they open the panel, **Then** Team is still drawn and leads nowhere.

---

### Edge Cases

- Pressing G while typing in a field (including the menu's own search field) types the letter and does not toggle the menu.
- A long company name stays on one line in the button and does not push the panel off screen.
- On a phone, the panel fits the screen width and the tiles wrap to two columns.
- In dark mode, the button tint and the panel read as in the design.
- With the keyboard alone, focus stays inside the open panel, and a screen reader announces whether the menu is open.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The top bar MUST draw the company button as in the design: company name in bold, chevron, warm tint always visible (not only on hover or when open).
- **FR-002**: The panel MUST show, in order, the G hint, the search field with the placeholder "Type to go to a client, a service, or a person…", the four tiles, the YOURS heading and the four personal links on two columns.
- **FR-003**: Each tile MUST carry its icon, tint and shortcut label as in the design: Dashboard ^1 (blue), Clients ^2 (red), Services ^3 (yellow), Team ^4 (purple).
- **FR-004**: Dashboard MUST lead to the dashboard. Team MUST lead to the team members screen for a person allowed to open it. Profile, Logs, Security and access and Preferences MUST lead to their account screens.
- **FR-005**: Clients and Services, and Team for a person not allowed to open it, MUST be drawn like the other tiles and lead nowhere.
- **FR-006**: The G key, Escape, a click outside the panel and the button MUST keep opening and closing the menu as today.
- **FR-007**: Every string in the menu MUST be translated in every configured locale.
- **FR-008**: The Locations tile MUST be removed from the menu. The locations screen stays reachable from the settings.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The menu offers exactly four tiles and four personal links, and none of the former labels (People, Assets, Software, Licences, Reports) appears anywhere in it.
- **SC-002**: Side by side with the design at the same width, in light and dark mode, the button and the panel show no visible difference in layout, colour or icon.
- **SC-003**: Every destination that exists is reached in two actions from any screen: open the menu, follow the tile or link.
- **SC-004**: The panel is fully usable at a 375 pixel wide screen, with the keyboard alone, and with a screen reader.

## Assumptions

- Only the menu changes. The logo on the left and the name and avatar on the right of the top bar stay as they are; the design file shows the menu alone.
- The search field keeps doing nothing, as today. Searching is a later feature.
- The shortcut labels ^1 to ^4 are drawn only; the keys themselves are not wired in this feature, as today.
- A tile that leads nowhere is not dimmed, so the menu looks finished, which differs from the current menu where such tiles are faded.
- The colours come from tokens the app already has (the warm tint and the menu background).
