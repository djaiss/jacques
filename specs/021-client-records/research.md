# Research: Client records

## R1. Phone normalization

- Decision: add `giggsey/libphonenumber-for-php-lite` and a helper `App\Helpers\PhoneNumber::normalize(string $phone, ?string $country): ?string` returning E.164, or null when the number cannot be parsed.
- Rationale: D18 demands comparison after normalization, and hand written rules fail on national prefixes. The lite package parses and formats without the geocoding and carrier data.
- The country comes from the location the number is entered at (`locations.country`). The company has no country column.
- Rejected: `propaganistas/laravel-phone` (adds a cast and rules nobody needs yet); a regular expression.

## R2. The phone stays readable

- Decision: `clients.phone` is plain text with a unique index on `(company_id, phone)`.
- Rationale: the `encrypted` cast changes the ciphertext on every write, so it cannot carry a unique index nor a lookup. NULL phones (anonymized clients) do not collide on SQLite, MySQL or PostgreSQL.
- Rejected: an encrypted phone plus a hash column (two columns for one fact, no gain while the database is ours).

## R3. Free text is encrypted

- Decision: `clients.special_instructions`, `client_notes.body` and `client_technical_notes.body` use the `encrypted` cast, on `text` columns.
- Rationale: they may hold health information (allergies, scalp conditions). Nobody searches them.

## R4. Foreign keys and deletion

| Column | On delete | Why |
| --- | --- | --- |
| `*.company_id`, `*.client_id`, `appointment_services.appointment_id` | cascade | `DestroyCompany` relies on the database cascade |
| `appointments.staff_id`, `appointments.location_id` | set null | history survives; a restrict would block the company cascade on MySQL |
| `clients.preferred_staff_id`, author and recorder staff ids | set null | the name snapshot keeps the author readable |
| `appointment_services.service_id` | set null | the snapshot carries name, price and duration |

- `DestroyStaff` refuses to delete a staff member any appointment mentions (D3), so set null on `appointments.staff_id` is only a safety net.

## R5. Snapshot of a performed service

- Decision: `appointment_services` copies `name` (the JSON map of the service, every salon language), `price` (smallest unit), `currency` and `duration` (minutes, the sum of the steps).
- Rationale: matches how `services` stores names and prices; a later catalogue edit never reaches the past (SC-002).

## R6. Indicators live in a helper

- Decision: `App\Helpers\ClientIndicators`, built from one client, loads the appointments once and exposes `visitCount()`, `lifetimeSpending()` (per currency), `lastVisit()`, `averageVisitInterval()` (days, null below two visits), `favouriteService()`, `nextAppointment()` and `segment()`.
- A visit is an appointment that has started and is `scheduled` or `attendance_confirmed` (D12).
- Segment thresholds are constants of the helper: `NEW_CLIENT_VISIT_LIMIT = 2`, `INACTIVE_AFTER_MONTHS = 6`.
- Rejected: accessors on `Client` (the models skill forbids business logic in models); stored counters (FR-010).

## R7. Technical notes carry the client

- Decision: `client_technical_notes.client_id` is stored next to `appointment_service_id`.
- Rationale: listing a client's notes would otherwise cross three tables. The factory and the future action set both from the same appointment.

## R8. Enums

`AppointmentStatusEnum` (Scheduled, AttendanceConfirmed, Cancelled, NoShow), `GenderEnum` (Female, Male, NonBinary), `ConsentChannelEnum` (Sms, Email), `ClientSegmentEnum` (New, Regular, Inactive).
