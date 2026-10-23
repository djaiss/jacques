# Data model: Client records

Every table has `id` and timestamps. Foreign key behaviour is in [research.md](research.md) R4.

## clients

| Column | Type | Note |
| --- | --- | --- |
| company_id | fk | |
| first_name, last_name | string, nullable | required by actions, emptied on erasure |
| phone | string(20), nullable | E.164 (R1); unique with company_id |
| email | string, nullable | |
| gender | string, nullable | `GenderEnum` |
| birth_date | date, nullable | |
| address_line_1, address_line_2, city, region, postal_code, country | nullable | same names as `locations` |
| preferred_staff_id | fk staff, nullable | |
| special_instructions | text, nullable, encrypted | |
| receives_sms_reminders | boolean, default true | |
| is_online_booking_blocked | boolean, default false | |
| anonymized_at | datetime, nullable | set by the future erasure action |

Relations: `company`, `preferredStaff`, `appointments`, `notes`, `technicalNotes`, `consents`.

## client_notes

`client_id`, `staff_id` (nullable, author), `author_name` (string), `body` (text, encrypted). Dated by `created_at`.

## appointments

| Column | Type | Note |
| --- | --- | --- |
| company_id, client_id | fk | |
| location_id, staff_id | fk, nullable | R4 |
| starts_at, ends_at | datetime | UTC |
| status | string, default `scheduled` | `AppointmentStatusEnum` |

Index `(client_id, starts_at)` and `(staff_id, starts_at)`. Relations: `company`, `client`, `location`, `staff`, `services` (ordered by position).

## appointment_services

`appointment_id`, `service_id` (nullable), `name` (json), `price` (unsigned integer), `currency` (char 3), `duration` (unsigned integer, minutes), `position` (unsigned integer). Relations: `appointment`, `service`, `technicalNotes`.

## client_technical_notes

`client_id`, `appointment_service_id`, `staff_id` (nullable, author), `author_name`, `body` (text, encrypted). Relations: `client`, `appointmentService`, `staff`.

## client_consents

`client_id`, `channel` (`ConsentChannelEnum`), `is_granted` (boolean), `staff_id` (nullable, null when the client gave it). Rows are never updated; the latest per channel is the current state.

## Existing models touched

- `Company`: `clients()`, `appointments()`.
- `Staff`: `appointments()`.
- `DestroyStaff`: refuses when `appointments()` exists (D3).
