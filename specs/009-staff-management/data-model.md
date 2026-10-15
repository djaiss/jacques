# Data Model: Staff Management

Table order and key behaviour: [research.md](research.md) R2. Rules: [spec.md](spec.md) FR-001 to FR-016.

## staff (model `Staff`, replaces `Employee`)

| Column | Type | Notes |
| --- | --- | --- |
| company_id | unsigned big integer | key to companies |
| user_id | unsigned big integer, nullable, unique | key to users, nulled on delete |
| first_name | string | |
| last_name, display_name, email, phone, job_title | string, nullable | |
| bio | text, nullable | |
| avatar_path | string, nullable | kept from employees (R3) |
| is_active, is_bookable | boolean, default true | |
| position | unsigned integer, default 0 | the next position in the company on create |

Relations: `company`, `user`, `locations` (through `location_staff`, with `is_active` on the pivot), `locationServices` (through `staff_location_services`), `workingHours`, `absences`. Accessors `name` and `full_name` carry over; `full_name` omits a missing last name.

## location_staff (model `LocationStaff`, replaces `UserLocation`)

`location_id`, `staff_id`, `is_active` boolean default true. Unique on (`location_id`, `staff_id`).
Removing an assignment also removes the staff member's services, working hours and location
specific absences at that location.

## staff_location_services (model `StaffLocationService`, replaces `TeamMemberService`)

`staff_id`, `location_service_id`. Unique on (`staff_id`, `location_service_id`).

## staff_working_hours (model `StaffWorkingHour`)

| Column | Type | Rule |
| --- | --- | --- |
| staff_id, location_id | unsigned big integer | staff assigned to the location |
| day_of_week | unsigned tiny integer | 0 (Sunday) to 6 (Saturday) |
| start_time, end_time | time | end after start, no overlap on the same day (R6) |

## staff_absences (model `StaffAbsence`)

| Column | Type | Rule |
| --- | --- | --- |
| staff_id | unsigned big integer | |
| location_id | unsigned big integer, nullable | null means every location; otherwise a location the staff member is assigned to |
| starts_at, ends_at | datetime, UTC | end after start |
| reason | string, nullable | |
| notes | text, nullable | |

## Changes to existing tables and models

- `users`: `employee_id` is removed. `User` gains `staff(): HasOne`.
- `Location`: `users()` becomes `staff()`. `LocationService`: `users()` becomes `staff()`.
