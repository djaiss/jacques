# Feature Specification: Add Favicon

**Feature Branch**: `2026-10-15-favicon`

**Created**: 2026-10-15

**Status**: Draft

**Input**: User description: "add favicon", with a 32 by 32 pixel image attached: a white portrait of
a man in a hat, on a black disc. The image is kept as [favicon-source.png](favicon-source.png).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Recognize Jacques among open tabs (Priority: P1)

Pam Beesly keeps the back office open all day next to her email and her calendar. Today the browser
tab shows a blank page icon, because the icon file the pages announce is empty. With the favicon,
the tab shows the Jacques portrait and Pam finds it at a glance.

**Independent Test**: open any page of the application in a browser and confirm the tab shows the
portrait instead of the generic page icon.

**Acceptance Scenarios**:

1. **Given** a signed in user on any back office screen, **When** the page loads, **Then** the tab
   shows the portrait.
2. **Given** a visitor on the sign in screen or the root page, **When** the page loads, **Then** the
   tab shows the portrait.
3. **Given** a browser that asks for the icon at its conventional address without reading the page,
   **When** it requests it, **Then** it receives the portrait.

---

### User Story 2 - Recognize Jacques in bookmarks and history (Priority: P2)

Michael Scott bookmarks the agenda. The bookmark and the history entry carry the same portrait as
the tab.

**Independent Test**: bookmark a screen and confirm the bookmark shows the portrait.

**Acceptance Scenarios**:

1. **Given** a bookmarked screen, **When** the bookmarks are listed, **Then** the entry shows the
   portrait.

### Edge Cases

- The browser uses a dark theme: the black disc with its white figure stays legible on a dark tab
  bar, so one icon serves both themes.
- A page is served without the shared page head (the root page today): the icon still appears,
  because browsers fall back to the conventional address.
- The screen has a high pixel density: the 32 pixel source is shown scaled, which is accepted for
  this feature (see Assumptions).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every page the application serves MUST announce the portrait as its icon.
- **FR-002**: The icon at the conventional address browsers request on their own MUST be the
  portrait and MUST NOT be empty.
- **FR-003**: The icon MUST be the attached image, unchanged in drawing and colour.
- **FR-004**: An automated test MUST fail if the icon file is missing or empty, or if a page stops
  announcing it.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of the application's page layouts, plus the root page, show the portrait in the
  browser tab.
- **SC-002**: A request for the icon at its conventional address returns the portrait, not an
  empty response.
- **SC-003**: A user picks the Jacques tab out of ten open tabs on the first attempt.

## Assumptions

- The attached 32 by 32 pixel image is the only source. A home screen icon for phones and a high
  density version need a larger source and are out of scope until one is supplied.
- The colourful logo used inside the application stays as it is. The favicon is the monochrome
  portrait, which reads better at tab size.
- No translation is involved: the feature adds no visible text.
