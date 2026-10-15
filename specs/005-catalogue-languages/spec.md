# Feature Specification: Catalogue Languages

**Feature Branch**: `2026-10-14-catalogue-languages` | **Created**: 2026-10-14 | **Status**: Draft

**Input**: User description: "We need to manage multiple languages for services, service
categories and steps. In Quebec we support two languages, in other countries this might be more.
We also need to set up supported languages in the first onboarding screen: users can add one or
more languages." Design: claude.ai design project `Onboarding Salon.dc.html`, block "Languages
spoken at the salon" of step 1 (004).

## Clarifications

### Session 2026-10-14

- Q: Is every salon language required, or one with a fallback? → A: Every salon language is required.
- Q: Steps carry only durations (003). Do they gain a translatable name? → A: No. Steps stay out of this feature.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The owner chooses the salon's languages during onboarding (Priority: P1)

On step 1 of the onboarding (004), under the time zone, the owner sees "Languages spoken at the
salon": one toggle per offered language, a counter, and the help text "Clients can book in these
languages, and your team can say which ones they speak." The country preselects its usual languages.

**Independent Test**: sign up, choose Canada, keep English and French, submit, and check the salon
has exactly those two languages.

**Acceptance Scenarios**:

1. **Given** step 1, **When** the owner selects Canada, **Then** English and French are selected and nothing else.
2. **Given** step 1 with one language selected, **When** the owner tries to deselect it, **Then** it stays selected and the counter says at least one is required.
3. **Given** step 1, **When** the owner selects English, French and Spanish and submits, **Then** the salon has these three languages, in that order.
4. **Given** the confirmation, **When** the owner presses "Edit the salon", **Then** the languages saved are shown selected.

### User Story 2 - The owner writes the catalogue in each salon language (Priority: P1)

When creating or editing a category or a service, the owner fills in its text once per salon
language: "Coupe femme" in French and "Women's haircut" in English for the same service.

**Independent Test**: with English and French, create a category and a service, and
check each text is stored and shown in both languages.

**Acceptance Scenarios**:

1. **Given** a salon in English and French, **When** the owner opens the service form, **Then** each text field is offered once per language, labelled with the language.
2. **Given** a salon in English and French, **When** the owner saves a service with its name in French only, **Then** nothing is saved and the English name field says it is required.
3. **Given** a service, **When** the owner edits its English name, **Then** the French name is unchanged.
4. **Given** a salon with one language, **When** the owner opens a form, **Then** each text field shows once, with no language label.

### User Story 3 - Languages change after onboarding (Priority: P2)

The owner adds or removes a language by editing step 1 again. Adding one leaves the existing
catalogue texts in place, and each item must gain its text in the new language the next time it
is saved. Removing one warns first.

**Independent Test**: add Spanish to an English and French salon, then remove it, and check what
the catalogue holds each time.

**Acceptance Scenarios**:

1. **Given** a salon in English and French with services, **When** Spanish is added, **Then** every text in English and French is unchanged, and the catalogue shows which items still miss their Spanish text.
2. **Given** a salon with Spanish texts, **When** the owner deselects Spanish, **Then** a warning names what will no longer be shown before anything is saved.
3. **Given** Spanish removed then added back, **When** the owner opens a service, **Then** its former Spanish texts are shown again.

### Edge Cases

- Two services, or two categories, of one salon cannot share a name in the same language; they can across languages.
- Changing the country after choosing languages resets the selection to that country's languages, as the design does.
- A salon content text is never machine translated (constitution, principle V).
- Every company that exists before this feature gets one language, and its current texts become texts in that language.
- The toggles work with the keyboard alone and announce their pressed state; the flag is decorative and the language name carries the meaning.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A company MUST have one or more salon languages, kept in the order they were selected.
- **FR-002**: Step 1 of the onboarding MUST show the language block of the design: label, counter, toggles with flag and native name, help text.
- **FR-003**: The offered languages MUST be English, French, Spanish, Portuguese, Italian, German, Arabic and Chinese, each shown by its own native name.
- **FR-004**: Selecting a country MUST preselect its usual languages: Canada English and French, France and Belgium French, Switzerland French and German, United States English, any other country English.
- **FR-005**: Step 1 MUST refuse a submission with no language, and the last selected language MUST NOT be deselectable.
- **FR-006**: The counter MUST state the number of selected languages through a plural aware phrase, and MUST say at least one is required when one is left.
- **FR-007**: The name and description of a category and of a service MUST be stored once per salon language.
- **FR-008**: A name MUST be unique within the company per language, for categories and for services.
- **FR-009**: Every form creating or editing a category or a service MUST offer one field per salon language for each text, labelled with the language when there are several.
- **FR-010**: Saving a category or a service MUST require its name in every salon language; a description stays optional in each.
- **FR-011**: Adding a language MUST NOT change any existing text, and the catalogue MUST show which items miss a text in it. Removing one MUST warn first, then hide its texts without deleting them.
- **FR-012**: Every screen listing the catalogue in the back office MUST show texts in the salon language matching the interface language of the viewer, else in the first salon language, and in the first salon language that has one when a text is missing.
- **FR-013**: The public API MUST read and write every language of every catalogue text, through the same operations as the back office.
- **FR-014**: The salon languages MUST be readable and writable through the public API.
- **FR-015**: Existing companies MUST be migrated to one language, the first one FR-004 gives for the country of their headquarters, and their current texts kept as texts in it.
- **FR-016**: Only an Owner MUST be able to change the salon languages.
- **FR-017**: Every new interface text MUST be translated in every configured locale, and the screens MUST meet WCAG 2.2 AA.

### Key Entities

- **Salon language**: one language a company works in, with its rank. The first one is the fallback.
- **Catalogue text**: one text of a category or a service, in one salon language.
- **Service category, Service** (003): their texts move from one value to one value per language.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An owner in Quebec completes step 1 with English and French in under 2 minutes, with no change to the preselection.
- **SC-002**: An owner enters a two language service in under 3 minutes.
- **SC-003**: 100% of companies existing before the release keep every catalogue text, unchanged, in one language.
- **SC-004**: 0 catalogue texts are lost when a language is removed and added back.
- **SC-005**: A salon can hold up to 8 languages with every form still usable on a phone.

## Assumptions

- Salon languages are independent of interface locales (`config/jacques.php`): a salon may write its catalogue in Portuguese while the interface has no Portuguese translation.
- Languages are changed from step 1 only, through "Edit the salon". A settings screen for them comes later.
- Which language the public booking page uses, and which languages a team member speaks, are out of scope. D30 ("the booking page speaks the language of the salon") needs amending to "the languages of the salon" before the booking page is built.
- Service steps are not translated: they hold no text. Appointment snapshots (003, FR-018) will store the texts in every language as they were at booking.
- No webhook: the catalogue has none yet.
