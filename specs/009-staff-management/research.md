# Research: Staff Management

## R1. Existing migrations are edited, no data is carried over

- Decision: rewrite the `employees`, `user_locations` and `team_member_services` migrations in place.
- Rationale: the migrations skill requires editing existing migrations until production (end of 2026), so no rows exist to move.
- Rejected: a new migration that renames tables and copies rows.

## R2. Migration order

| File | Change |
| --- | --- |
| `0000_01_01_000001_create_employees_table.php` | deleted |
| `0001_01_01_000000_create_users_table.php` | `employee_id` and its key removed |
| `0001_01_01_000003_create_staff_table.php` | new, runs after users so `user_id` can point to it |
| `2026_08_01_000005_create_location_staff_table.php` | replaces `create_user_locations_table` |
| `2026_10_14_000004_create_staff_location_services_table.php` | replaces `create_team_member_services_table` |
| `2026_10_15_000000_create_staff_working_hours_table.php` | new |
| `2026_10_15_000001_create_staff_absences_table.php` | new |

Every key cascades on delete, except `staff.user_id`, which nulls on delete so that deleting an account keeps the person.

## R3. Columns kept from Employee

- Decision: `avatar_path` stays on `staff`; `work_email` becomes `email`; `employee_number` is dropped.
- Rationale: the avatar upload works today and FR-015 says nothing that works today is lost.

## R4. Permission scope goes through the staff record

- Decision: `User` gets `staff(): HasOne`. Location scope reads the staff member's assignments where `location_staff.is_active` is true. An account without a staff record has no location and no self scope, as an account without an employee has today.
- `PendingPermissionCheck::forEmployee()` becomes `forStaff()`. `EmployeeScope` becomes `StaffScope`. `User::locations()` and `User::locationServices()` are removed, and every caller reads `$user->staff`.
- Rejected: keeping `User::locations()` as a relation. Laravel cannot express a many to many relation through a has one relation without a package.

## R5. Permissions

| Operation | Permission | Checked with |
| --- | --- | --- |
| create staff | `staff.create` (was `employee.create`) | `forLocation` of the first location, or `forCompany` with no location |
| view, update, reorder, activate, deactivate staff | `staff.view`, `staff.update` (were `employee.*`) | `forStaff` |
| delete staff | `staff.delete` (new, company scope, Owner only by default) | `forCompany` |
| assign, pause, remove a location | `role.manage`, as today | `forCompany` |
| services at a location | `service.manage`, as today | `forLocation` |
| working hours, absences | `schedule.manage` | `forStaff` |

The `employee.*` values are renamed because one word must mean one thing. `CreateDefaultRoles` and its matrix follow, and so do the labels and translations.

## R6. Working hour overlap

- Decision: the action refuses a range when another range of the same staff member, location and day has `start_time < new end` and `end_time > new start`. Times are compared as `H:i:s` strings.
- Rationale: a plain string comparison gives the same result on SQLite, MySQL and PostgreSQL.

## R7. Absence time zone

- Decision: actions take `CarbonImmutable` values and store them with `->utc()`. The application time zone is already UTC. Converting from the salon's time zone belongs to the screen that collects the dates, which a later feature builds.

## R8. Gaps the constitution allows if the pull request states them

- Public API (VII): `routes/api.php` does not exist yet. The actions are written so the API can reuse them.
- Webhooks (VII): no webhook infrastructure exists yet. Every action logs through `LogUserAction`, which webhooks will read from later.
