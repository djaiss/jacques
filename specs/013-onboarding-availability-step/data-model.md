# Data Model: Onboarding Step 4, Availability

## New table `location_opening_hours`

Migration `2026_10_16_000000_create_location_opening_hours_table.php`, `up()` only, foreign key at the bottom.

| Column | Type | Comment |
| --- | --- | --- |
| `id` | id | primary key |
| `location_id` | unsignedBigInteger | location open during the range |
| `day_of_week` | unsignedTinyInteger | day of the week the range repeats on, from 0 for Sunday to 6 for Saturday |
| `start_time` | time | time the range starts, in the time zone of the location |
| `end_time` | time | time the range ends, in the time zone of the location |
| timestamps | | |

Foreign key `location_id` → `locations.id`, cascade on delete. Index `(location_id, day_of_week)`.

Model `LocationOpeningHour`, factory `LocationOpeningHourFactory`, `day_of_week` cast to integer, `location()` belongs to. `Location` gains `openingHours()` has many.

## Changed table `location_staff`

Added to `2026_08_01_000005_create_location_staff_table.php` (R3):

| Column | Type | Comment |
| --- | --- | --- |
| `follows_opening_hours` | boolean, default true | whether the staff member works the opening hours of the location rather than hours of their own |

`Staff::locations()` and `Location::staff()` add it to `withPivot`.

## Existing table `staff_working_hours`

Unchanged. Written by `SyncStaffWorkingHours` (R2).

## Rules

| Rule | Where |
| --- | --- |
| Time written `HH:MM`, end after start, no overlap within a day | `SyncLocationOpeningHours`, `SyncStaffWorkingHours` |
| Day of the week from 0 to 6 | both sync actions |
| Times on a 30 minute grid from 06:00 to 22:00 | form rules of both controllers |
| Location of the author's company, else `ModelNotFoundException` | both sync actions, `ApplyOpeningHoursToTeam` |
| Staff assigned to the location | `SyncStaffWorkingHours` |

## Enum changes

| Enum | Change |
| --- | --- |
| `OnboardingStepEnum` | `Appointments` becomes `Availability`, label "Availability", route `onboarding.availability.index` |
| `UserActionEnum` | `LocationOpeningHoursUpdated` ("Changed the opening hours of the location called :location"), `StaffWorkingHoursUpdated` ("Changed the working hours of :name at the location called :location") |

## Default week (spec FR-005)

Held by the view model, not stored until "Finish setup": Monday to Wednesday 09:00 to 18:00, Thursday and Friday 09:00 to 20:00, Saturday 09:00 to 17:00, Sunday closed.
