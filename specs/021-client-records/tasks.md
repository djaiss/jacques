# Tasks: Client records

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md`, with people from The Office. Models follow `.claude/skills/models/SKILL.md`, migrations `.claude/skills/migrations/SKILL.md` (`up()` only, `->comment()` on every column, foreign keys declared at the bottom, no `constrained()`).

## Phase 1: Setup

- [X] T001 Run `composer require -q giggsey/libphonenumber-for-php-lite` (research R1), updating composer.json and composer.lock

## Phase 2: Foundational

- [X] T002 [P] Create app/Enums/AppointmentStatusEnum.php: string backed, `Scheduled = 'scheduled'`, `AttendanceConfirmed = 'attendance_confirmed'`, `Cancelled = 'cancelled'`, `NoShow = 'no_show'`, and `countsAsVisit(): bool` true for the first two (D12)
- [X] T003 [P] Create app/Enums/GenderEnum.php (`Female = 'female'`, `Male = 'male'`, `NonBinary = 'non_binary'`), app/Enums/ConsentChannelEnum.php (`Sms = 'sms'`, `Email = 'email'`) and app/Enums/ClientSegmentEnum.php (`New = 'new'`, `Regular = 'regular'`, `Inactive = 'inactive'`), one per file
- [X] T004 [P] Create tests/Unit/Enums/AppointmentStatusEnumTest.php: `countsAsVisit()` through a dataset of the four cases
- [X] T005 Create app/Helpers/PhoneNumber.php with `static normalize(string $phone, ?string $country): ?string`, parsing with `PhoneNumberUtil::getInstance()`, returning E.164, null when unparsable or invalid
- [X] T006 [P] Create tests/Unit/Helpers/PhoneNumberTest.php: "(570) 555-0100" with `US` and "+1 570 555 0100" with null both give `+15705550100`, "06 12 34 56 78" with `FR` gives `+33612345678`, "abc" gives null, a national number with a null country gives null

## Phase 3: User Story 1, one record per client (P1)

Independent test: Michael Scott recorded in Dunder Mifflin is invisible from another company, and a second record with the same phone written differently fails.

- [X] T007 [US1] Create database/migrations/2026_10_23_000000_create_clients_table.php with the columns of data-model.md `clients`: `phone` string(20) nullable, `unique(['company_id', 'phone'])`, `special_instructions` text nullable, `receives_sms_reminders` boolean default true, `is_online_booking_blocked` boolean default false, `anonymized_at` datetime nullable; `company_id` cascade, `preferred_staff_id` null on delete (research R4)
- [X] T008 [US1] Create database/migrations/2026_10_23_000005_create_client_consents_table.php: `client_id` cascade, `channel` string, `is_granted` boolean, `staff_id` nullable null on delete with comment "staff member who recorded the change, null when the client gave it", index `(client_id, channel, created_at)`
- [X] T009 [US1] Create app/Models/Client.php: full `@property` docblock, casts (`gender` to `GenderEnum`, `birth_date` date, `special_instructions` encrypted, two booleans, `anonymized_at` datetime), relations `company()`, `preferredStaff()`, `consents()`; `appointments()`, `notes()`, `technicalNotes()` are added by their stories
- [X] T010 [P] [US1] Create app/Models/ClientConsent.php: casts `channel` to `ConsentChannelEnum`, `is_granted` boolean; relations `client()`, `staff()`
- [X] T011 [US1] Add `clients()` HasMany to app/Models/Company.php
- [X] T012 [P] [US1] Create database/factories/ClientFactory.php (company via `Company::factory()`, fake names, `+1570555` followed by four random digits, nullable fields null) and database/factories/ClientConsentFactory.php
- [X] T013 [US1] Create tests/Unit/Models/ClientTest.php: relations exist, casts, a duplicate phone in one company throws `UniqueConstraintViolationException`, the same phone in two companies passes, two clients with a null phone pass, every personal field set to null saves (SC-004 is completed in T022)
- [X] T014 [P] [US1] Create tests/Unit/Models/ClientConsentTest.php: relations, casts, SMS granted then withdrawn keeps two rows in order and the latest is withdrawn
- [X] T015 [US1] Extend tests/Unit/Models/CompanyTest.php with `clients()`

## Phase 4: User Story 2, history of visits and services (P1)

Independent test: an appointment of Michael with two services keeps its price after the catalogue price of Men's Haircut changes.

- [X] T016 [US2] Create database/migrations/2026_10_23_000002_create_appointments_table.php: `company_id` and `client_id` cascade, `location_id` and `staff_id` nullable null on delete, `starts_at` and `ends_at` datetime "in UTC", `status` string default `scheduled`, indexes `(client_id, starts_at)` and `(staff_id, starts_at)`
- [X] T017 [US2] Create database/migrations/2026_10_23_000003_create_appointment_services_table.php: `appointment_id` cascade, `service_id` nullable null on delete, `name` json, `price` unsigned integer "in the smallest unit", `currency` char(3), `duration` unsigned integer "in minutes", `position` unsigned integer default 0
- [X] T018 [US2] Create app/Models/Appointment.php (casts `starts_at`, `ends_at` datetime, `status` to `AppointmentStatusEnum`; relations `company()`, `client()`, `location()`, `staff()`, `services()` ordered by `position`) and app/Models/AppointmentService.php (casts `name` array, integers; relations `appointment()`, `service()`; `localizedName(string $locale)` falling back like `Service::localizedName`)
- [X] T019 [US2] Add `appointments()` HasMany to app/Models/Client.php, app/Models/Company.php and app/Models/Staff.php
- [X] T020 [P] [US2] Create database/factories/AppointmentFactory.php (client and company from the same `Company`, a staff and a location of that company, one hour long, `scheduled`) and database/factories/AppointmentServiceFactory.php (copies name, first price and currency, and duration from a `Service::factory()`)
- [X] T021 [US2] In app/Actions/DestroyStaff.php refuse with a `ValidationException` when `$this->staff->appointments()->exists()` (D3), before any file is deleted
- [X] T022 [US2] Create tests/Unit/Models/AppointmentTest.php and tests/Unit/Models/AppointmentServiceTest.php: relations, casts, services in position order, price kept after the service price changes (SC-002), `service_id` null after the service is deleted while the row stays; in tests/Unit/Models/ClientTest.php add: appointments remain after every personal field is nulled (SC-004)
- [X] T023 [US2] Extend tests/Unit/Actions/DestroyStaffTest.php: Jim with an appointment is refused and still exists; extend tests/Unit/Models/StaffTest.php with `appointments()`

## Phase 5: User Story 3, notes and technical notes (P2)

Independent test: two colourings of Pam, each with a technical note by Kelly Kapoor, list newest first.

- [X] T024 [US3] Create database/migrations/2026_10_23_000001_create_client_notes_table.php (`client_id` cascade, `staff_id` nullable null on delete, `author_name` string, `body` text) and database/migrations/2026_10_23_000004_create_client_technical_notes_table.php (same plus `appointment_service_id` cascade, index `(client_id, created_at)`)
- [X] T025 [US3] Create app/Models/ClientNote.php and app/Models/ClientTechnicalNote.php: `body` encrypted; relations `client()`, `staff()`, and `appointmentService()` on the technical note; add `notes()` and `technicalNotes()` (latest first) to app/Models/Client.php and `technicalNotes()` to app/Models/AppointmentService.php
- [X] T026 [P] [US3] Create database/factories/ClientNoteFactory.php and database/factories/ClientTechnicalNoteFactory.php, the latter taking `client_id` from the appointment of its appointment service (research R7)
- [X] T027 [US3] Create tests/Unit/Models/ClientNoteTest.php and tests/Unit/Models/ClientTechnicalNoteTest.php: relations, `body` unreadable in the raw row, author name kept and `staff_id` null after the author is deleted, technical notes of a client newest first

## Phase 6: User Story 4, indicators of a client (P2)

Independent test: Michael with known visits gives each indicator and the segment by hand count.

- [X] T028 [US4] Create app/Helpers/ClientIndicators.php per research R6: constructor loads the client's appointments with services once; `visitCount()`, `lifetimeSpending(): array<string, int>` keyed by currency, `lastVisit(): ?Appointment`, `averageVisitInterval(): ?int` in days, `favouriteService(): ?string` (most frequent localized name, earliest on a tie), `nextAppointment(): ?Appointment` (future, not cancelled), `segment(): ClientSegmentEnum` with `NEW_CLIENT_VISIT_LIMIT = 2` and `INACTIVE_AFTER_MONTHS = 6`; "now" from `now()`
- [X] T029 [US4] Create tests/Unit/Helpers/ClientIndicatorsTest.php with `travelTo()`: visits on Jan 1, Feb 5, Mar 12 give 35 days; cancelled and no show excluded from visits and spending; spending split by currency; no visit gives zero, nulls and New; last visit seven months ago gives Inactive; three recent visits give Regular; next appointment skips cancelled ones

## Phase 7: Polish

- [X] T030 Run `php artisan migrate:fresh -q`, `php artisan test --compact`, `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q`; fix every finding
- [X] T031 Run `php artisan jacques:localize` and confirm it reports nothing missing; run every row of quickstart.md

## Dependencies

- T001 before T005. Phase 2 before every story.
- US1 (T007 to T015) before US2, US3 and US4: they all reference `clients`.
- US2 before US3 (technical notes reference `appointment_services`) and before US4 (indicators read appointments).
- US3 and US4 are independent of each other.
- Inside a phase, the migration comes before the model, the model before its factory, the factory before its test.
