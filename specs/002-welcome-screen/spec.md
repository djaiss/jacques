# Feature Specification: Welcome Screen

**Feature Branch**: `2026-10-14-welcome-screen` | **Created**: 2026-10-14 | **Status**: Draft

**Input**: User description: "Create a new welcome page upon account creation. This is the first
screen users see after creating an account. It is seen only once, through a boolean flag on the
company. Pressing the button sets the flag to false and the screen is not shown again. Use the same
logo." Design: claude.ai design project `Welcome.dc.html`.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new salon owner is welcomed once (Priority: P1)

Somebody signs up and sees the email confirmation notice, as today. When they first enter the
application, by confirming their email address or by leaving the notice, they see a welcome screen with the
Jacques logo, a greeting with their first name, a short letter from the creator of Jacques, and one
button. Pressing it takes them to the home screen, and the welcome screen never appears again for
their company.

**Why this priority**: it is the whole feature.

**Independent Test**: sign up, check the welcome screen shows, press the button, check the home
screen shows and the welcome screen does not come back.

**Acceptance Scenarios**:

1. **Given** a company that was just created, **When** its user confirms their email address or opens any screen past the notice, **Then** the welcome screen shows, greeting them by first name.
2. **Given** the welcome screen, **When** the user presses the button, **Then** the company is marked as welcomed and the home screen shows.
3. **Given** a welcomed company, **When** any of its users opens any screen, including the welcome screen address, **Then** the welcome screen does not show and they reach that screen, or home, instead.
4. **Given** a company not yet welcomed, **When** its user opens another screen of the application directly, **Then** they are sent to the welcome screen first.

### Edge Cases

- The user closes the browser before pressing the button: the welcome screen shows again at the next visit.
- Two tabs are open on the welcome screen: pressing the button in both is harmless and leaves the company welcomed.
- A user of another company cannot mark this company as welcomed.
- Companies that existed before this feature are treated as already welcomed.
- The screen is usable on a phone, with the keyboard alone, with a screen reader, and in dark mode.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A company MUST carry a yes or no value saying whether its welcome screen is still to be shown.
- **FR-002**: A newly created company MUST have the welcome screen still to be shown; every company that exists before this feature MUST NOT.
- **FR-003**: Signing up MUST still lead to the email confirmation notice first. While the welcome screen is still to be shown, a signed in user of that company MUST be sent to it when opening any other screen of the signed in application, the email confirmation screens and signing out excepted.
- **FR-004**: The welcome screen MUST show the Jacques logo already used across the application, the title "Hi {first name}, welcome to Jacques.", the subtitle, the five paragraph letter and the signature "Regis, Creator of Jacques" from the design.
- **FR-005**: The welcome screen MUST have one button, labelled "Let's get started" as in the design.
- **FR-006**: Pressing the button MUST mark the company as welcomed and take the user to the home screen.
- **FR-007**: Once the company is welcomed, the welcome screen MUST NOT be shown again to any of its users, and its address MUST lead to the home screen.
- **FR-008**: Every text on the screen MUST be translated in every configured locale.
- **FR-009**: The screen MUST meet WCAG 2.2 AA on desktop, tablet and phone, in light and dark mode.

### Key Entities

- **Company**: gains one value, "welcome screen still to be shown", true at creation and false once the button is pressed.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of new companies see the welcome screen as their first screen after the email confirmation notice.
- **SC-002**: 0 companies see the welcome screen after its button was pressed.
- **SC-003**: 0 companies created before the release see the welcome screen.
- **SC-004**: A user goes from the welcome screen to the home screen in one action.

## Assumptions

- The flag lives on the company, as requested, so it is shared by every user of that company. At creation the company has a single user, the Owner, so in practice the Owner sees it.
- The letter is static copy, translated like the rest of the application. It is not personalized beyond the first name.
- The signature avatar shows the letter "R", as in the design, not a photograph.
- The screen sits outside the usual application frame (no top bar), centered, as the design shows.
- No API coverage: the flag is a back office presentation concern, not a capability a salon acts on (principle VII). No webhook is sent when it changes.
