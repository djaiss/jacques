# Implementation Plan: Client records

**Branch**: `2026-10-23-client-records` | **Date**: 2026-10-23 | **Spec**: [spec.md](spec.md)

## Summary

Six tables and their models give Jacques a client record and its salon file: `clients`,
`client_notes`, `appointments`, `appointment_services`, `client_technical_notes` and
`client_consents`. Phones are normalized to E.164 by a helper and unique per company. Indicators
and the segment are computed by a helper from the appointments, never stored. No screen, route,
action or API endpoint is added, except a guard in `DestroyStaff` for D3. Decisions:
[research.md](research.md). Schema: [data-model.md](data-model.md). Checks:
[quickstart.md](quickstart.md). No `contracts/`: nothing is exposed outside the application.

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: `giggsey/libphonenumber-for-php-lite` (new, R1)
- **Storage**: six new tables on SQLite, MySQL and PostgreSQL
- **Testing**: Pest unit tests per model, helper and enum touched; `DestroyStaffTest` extended
- **Target Platform**: web back office
- **Project Type**: Laravel web application
- **Performance Goals**: indicators of a client in one query on appointments with their services
- **Constraints**: no driver specific SQL; free text encrypted (R3); times in UTC
- **Scale/Scope**: 6 migrations, 6 models, 6 factories, 4 enums, 2 helpers, 1 action changed

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. No operation is added; indicators are read only computation in a helper (R6) |
| II. Simplicity | Pass. No preference table, no counters, no polymorphic notes |
| III. Tested | Pass. Every new model, enum with behaviour and helper gets a test file |
| IV. Laravel conventions | Pass. `up()` only, comments on every column, no `constrained()` |
| V. Localized and safe | Pass. No user facing string is added, so `jacques:localize` reports nothing |
| VI. Accessible | Not applicable: no screen |
| VII. One core | Deferred with justification: no action exists yet to expose; API comes with the client screens |
| VIII. Short record | Pass. Every artifact under its cap |
| D3 staff deleted only without appointments | Pass. Guard in `DestroyStaff` |
| D12 four statuses | Pass. `AppointmentStatusEnum` |
| D18 one phone, one record | Pass. Unique `(company_id, phone)` after normalization |
| D19 no photos, D22 no money, D24 no products | Pass. Spending is a sum of catalogue prices, not payments |
| D23 erasure keeps appointments | Pass. Personal columns nullable, `anonymized_at` ready |
| Company scoping | Pass. Every table reaches `company_id`; tests cover another company's records |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/021-client-records/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
├── checklists/requirements.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
database/migrations/2026_10_23_000000_create_clients_table.php
database/migrations/2026_10_23_000001_create_client_notes_table.php
database/migrations/2026_10_23_000002_create_appointments_table.php
database/migrations/2026_10_23_000003_create_appointment_services_table.php
database/migrations/2026_10_23_000004_create_client_technical_notes_table.php
database/migrations/2026_10_23_000005_create_client_consents_table.php
app/Models/{Client,ClientNote,Appointment,AppointmentService,ClientTechnicalNote,ClientConsent}.php
app/Models/{Company,Staff}.php                       new relations
database/factories/{Client,ClientNote,Appointment,AppointmentService,ClientTechnicalNote,ClientConsent}Factory.php
app/Enums/{AppointmentStatusEnum,GenderEnum,ConsentChannelEnum,ClientSegmentEnum}.php
app/Helpers/PhoneNumber.php                          normalize to E.164 (R1)
app/Helpers/ClientIndicators.php                     visits, spending, intervals, segment (R6)
app/Actions/DestroyStaff.php                         refuse when an appointment mentions the staff
tests/Unit/Models/*Test.php                          one per new model, Company and Staff extended
tests/Unit/Helpers/{PhoneNumber,ClientIndicators}Test.php
tests/Unit/Actions/DestroyStaffTest.php              refusal case
```

**Structure Decision**: existing folders only. Helpers go in `app/Helpers`, next to `WeeklyHours`
and `CatalogueSnapshot`, which play the same role.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| VII: no API in this feature | there is no action to expose yet | an API over raw models would bypass principle I |
| `client_id` duplicated on technical notes (R7) | list a client's formulas directly | a three table join on every read of the file |
