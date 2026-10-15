# Feature Specification: Salon Currencies

**Feature Branch**: `2026-10-14-salon-currencies` | **Created**: 2026-10-14 | **Status**: Draft

**Input**: User description: "We need to be able to define supported currencies for a company at
the salon level. By default we support CAD, but we should be able to let, like locales, define all
supported currencies on the first step of the onboarding."

## Clarifications

### Session 2026-10-14

- Q: What does a price mean with several currencies? → A: One amount per salon currency, all required.
- Q: What is selected by default? → A: The country preselects its currency, Canadian dollar for any other country.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The owner chooses the salon's currencies during onboarding (Priority: P1)

On step 1 of the onboarding (004), under "Languages spoken at the salon" (005), the owner sees
"Currencies accepted at the salon": one toggle per offered currency, a counter and a help text.
The country preselects its currency, and the owner adds or removes currencies like languages.

**Why this priority**: every price of the catalogue is expressed in a currency, and step 1 is where
the salon declares what it works with (D2).

**Independent Test**: sign up, keep Canadian dollar, add US dollar, submit, and check the salon has
exactly those two currencies, in that order.

**Acceptance Scenarios**:

1. **Given** step 1, **When** the owner selects Canada, **Then** Canadian dollar is selected and nothing else.
2. **Given** step 1, **When** the owner selects France, **Then** euro is selected and nothing else.
3. **Given** step 1 with one currency selected, **When** the owner tries to deselect it, **Then** it stays selected and the counter says at least one is required.
4. **Given** step 1, **When** the owner selects Canadian dollar then US dollar and submits, **Then** the salon has these two currencies, in that order.
5. **Given** the confirmation, **When** the owner presses "Edit the salon", **Then** the currencies saved are shown selected.

### User Story 2 - The catalogue is priced in the salon's currencies (Priority: P1)

When creating or editing a service, the owner fills in its price once per salon currency: 65 $ in
Canadian dollar and 48 $ in US dollar for the same service. A location may replace any of them.

**Why this priority**: a currency nobody can price in has no effect on the product.

**Independent Test**: with Canadian dollar and US dollar, price a service, and check the prices
stored and shown.

**Acceptance Scenarios**:

1. **Given** a salon in Canadian dollar and US dollar, **When** the owner opens the service form, **Then** the price is offered once per currency, labelled with the currency.
2. **Given** a salon in Canadian dollar and US dollar, **When** the owner saves a service with a Canadian dollar price only, **Then** nothing is saved and the US dollar price says it is required.
3. **Given** a location replacing the US dollar price only, **When** its prices are shown, **Then** the Canadian dollar price follows the service.
4. **Given** a salon with one currency, **When** the owner opens a form, **Then** the price shows once, with no currency label, and is shown with its symbol formatted for the language of the viewer.

### User Story 3 - Currencies change after onboarding (Priority: P2)

The owner adds or removes a currency by editing step 1 again. Removing one warns first and keeps
the prices written in it, so adding it back brings them back.

**Independent Test**: add euro to a Canadian dollar salon, then remove it, and check the prices
each time.

**Acceptance Scenarios**:

1. **Given** a salon in Canadian dollar with priced services, **When** US dollar is added, **Then** every Canadian dollar price is unchanged, and the catalogue shows which services still miss their US dollar price.
2. **Given** a salon with prices in US dollar, **When** the owner deselects US dollar, **Then** a warning names what will no longer be shown before anything is saved.
3. **Given** US dollar removed then added back, **When** the owner opens a service, **Then** its former US dollar prices are shown again.

### Edge Cases

- A currency appears at most once in the selection.
- Jacques converts no amount between currencies and fetches no exchange rate: a price exists only as the salon wrote it (D22).
- Each currency keeps its own number of decimals: a price in a currency without cents is stored without them.
- The toggles work with the keyboard alone and announce their pressed state; the currency name carries the meaning, never a symbol alone, since several currencies share "$".
- Every company that exists before this feature gets one currency: the one it has, or Canadian dollar when it has none.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A company MUST have one or more currencies, kept in the order they were selected; the first one is its main currency.
- **FR-002**: Step 1 of the onboarding MUST show a currency block built like the language block (005): label, counter, toggles, help text.
- **FR-003**: The offered currencies MUST be Canadian dollar, US dollar, euro, pound sterling and Swiss franc, each shown by its name in the interface language and its ISO 4217 code.
- **FR-004**: Selecting a country MUST preselect its currency: Canada Canadian dollar, United States US dollar, France and Belgium euro, Switzerland Swiss franc, any other country Canadian dollar.
- **FR-005**: Step 1 MUST refuse a submission with no currency, and the last selected currency MUST NOT be deselectable.
- **FR-006**: The counter MUST state the number of selected currencies through a plural aware phrase, and MUST say at least one is required when one is left.
- **FR-007**: Every price MUST be stored in the smallest unit of its currency.
- **FR-008**: A service MUST have one price per salon currency, each required when it is saved.
- **FR-009**: A location MAY replace the price of a service in any salon currency; a currency it does not replace follows the service.
- **FR-010**: Adding a currency MUST NOT change any existing price, and the catalogue MUST show which services miss a price in it. Removing one MUST warn first, then hide its prices without deleting them.
- **FR-011**: Every price shown MUST carry its currency and be formatted for the language of the viewer, never with a hardcoded symbol or separator.
- **FR-012**: The salon currencies MUST be readable and writable through the public API, and the prices through the same operations as the back office.
- **FR-013**: Existing companies MUST be migrated to one currency, their current one or Canadian dollar when none is set, and their current prices kept in it.
- **FR-014**: Only an Owner MUST be able to change the salon currencies.
- **FR-015**: The default catalogue (006) MUST keep its Canadian dollar amounts as prices in Canadian dollar; a salon choosing other currencies sees those services miss a price in them.
- **FR-016**: Every new interface text MUST be translated in every configured locale, and the screens MUST meet WCAG 2.2 AA.

### Key Entities

- **Salon currency**: one currency a company works with, by ISO 4217 code, with its rank. The first one is the main currency.
- **Price** (003): an amount of a service, or the replacement a location sets, in the smallest unit of one salon currency. Each service has one per salon currency.
- **Company** (004): its single currency becomes an ordered list of currencies.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An owner in Quebec completes step 1 in under 2 minutes with no change to the currency preselection.
- **SC-002**: 100% of companies existing before the release keep every price, unchanged, in one currency.
- **SC-003**: 0 prices are lost when a currency is removed and added back.
- **SC-004**: A salon holding all 5 offered currencies keeps every form usable on a phone.

## Assumptions

- Currencies are changed from step 1 only, through "Edit the salon". A settings screen for them comes later.
- The offered list is short on purpose and lives in configuration, like the content languages; adding a currency is a configuration change.
- D2 ("the salon declares its currency") needs amending to "its currencies" in `docs/specs/decisions.md` before this ships.
- Jacques still takes no payment (D22): a currency only decides how a price is written and shown.
- Which currency the public booking page shows is decided with the booking page. Appointment snapshots (003, FR-018) store the price in every currency as it was at booking.
- No webhook: the catalogue has none yet.
