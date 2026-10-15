# Tasks: Staff Management

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Tests**: required by the request and by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Amend D3 and the "Salon et équipe" models in docs/specs/decisions.md: a staff member may exist without an account, and staff working hours and absences replace ScheduleRule and ScheduleException for people (FR-017)
- [X] T002 Amend the invariant "A team member is a user account" in .specify/memory/constitution.md, add a Sync Impact Report entry and bump the version to 1.3.0 (FR-017)

## Phase 2: Foundational (replace Employee, UserLocation, TeamMemberService)

- [X] T003 Delete database/migrations/0000_01_01_000001_create_employees_table.php and create database/migrations/0001_01_01_000003_create_staff_table.php per data-model.md (`user_id` "nullable, unique", nulled on delete, `avatar_path` kept, `is_active` and `is_bookable` "boolean, default true", `position` "unsigned integer, default 0"), with only `up()`
- [X] T004 Remove `employee_id` and its foreign key from database/migrations/0001_01_01_000000_create_users_table.php
- [X] T005 Replace database/migrations/2026_08_01_000005_create_user_locations_table.php with database/migrations/2026_08_01_000005_create_location_staff_table.php (`location_id`, `staff_id`, `is_active` default true, unique on both keys)
- [X] T006 Replace database/migrations/2026_10_14_000004_create_team_member_services_table.php with database/migrations/2026_10_14_000004_create_staff_location_services_table.php (`staff_id`, `location_service_id`, unique on both keys)
- [X] T007 Create app/Models/Staff.php from app/Models/Employee.php (relations `company`, `user`, `locations` with `is_active` pivot, `locationServices`, accessors `name` and `full_name` with a nullable last name, avatar methods), then delete app/Models/Employee.php
- [X] T008 [P] Create app/Models/LocationStaff.php and app/Models/StaffLocationService.php, then delete app/Models/UserLocation.php and app/Models/TeamMemberService.php
- [X] T009 Edit app/Models/User.php: remove `employee_id`, `employee()`, `locations()` and `locationServices()`, add `staff(): HasOne`
- [X] T010 [P] Edit app/Models/Location.php and app/Models/LocationService.php: rename `users()` to `staff()` on `location_staff` and `staff_location_services`
- [X] T011 [P] Create database/factories/StaffFactory.php, LocationStaffFactory.php and StaffLocationServiceFactory.php, then delete EmployeeFactory.php, UserLocationFactory.php and TeamMemberServiceFactory.php
- [X] T012 Rename `EmployeeView`, `EmployeeCreate` and `EmployeeUpdate` to `StaffView` (`staff.view`), `StaffCreate` and `StaffUpdate`, add `StaffDelete` (`staff.delete`, company scope, Team group), and rename `targetsEmployee()` to `targetsStaff()` in app/Enums/PermissionEnum.php (R5)
- [X] T013 Rename the Employee*, UserAssignedToLocation, UserRemovedFromLocation and TeamMemberService* cases to Staff* names in app/Enums/UserActionEnum.php
- [X] T014 Replace `forEmployee()` with `forStaff()` in app/Permissions/PendingPermissionCheck.php, reading location scope from the active `location_staff` rows of `$user->staff` (R4)
- [X] T015 Create app/Permissions/StaffScope.php from app/Permissions/EmployeeScope.php with the R4 rules, then delete EmployeeScope.php
- [X] T016 Give the Owner `staff.delete` and use the renamed permissions in app/Actions/CreateDefaultRoles.php
- [X] T017 Create app/Actions/CreateStaff.php from CreateEmployee.php (optional `user`, an account of the same company that is not already linked; position set to the next one in the company), then delete CreateEmployee.php
- [X] T018 [P] Create UpdateStaffInformation.php, UpdateStaffAvatar.php and DestroyStaffAvatar.php in app/Actions from their Employee versions, then delete the Employee versions
- [X] T019 Edit app/Actions/CreateCompany.php to create the Owner's staff record with `user_id` set
- [X] T020 Make app/Actions/AssignLocation.php and app/Actions/RemoveLocation.php take a `Staff`; removal also deletes that staff member's services at the location
- [X] T021 Create app/Actions/AssignStaffLocationService.php and app/Actions/RemoveStaffLocationService.php from their TeamMemberService versions, taking a `Staff` and requiring an active assignment, then delete the old files
- [X] T022 Switch app/Http/Controllers/App/Settings/Account/Profile/AvatarController.php, app/Http/Controllers/App/Settings/Administration/LocationPeopleController.php and their routes in routes/web.php to a `{staff}` parameter
- [X] T023 [P] Make app/ViewModels/Settings/Account/Profile/ProfileViewModel.php, app/ViewModels/Settings/SettingsViewModel.php, app/ViewModels/Settings/Administration/RolesViewModel.php and app/ViewModels/Settings/Administration/LocationsViewModel.php read `staff`
- [X] T024 [P] Make resources/views/components/avatar.blade.php, resources/views/components/top-bar.blade.php and resources/views/layouts/top-bar.blade.php read `staff`
- [X] T025 Create a staff record for every seeded account in database/seeders/DatabaseSeeder.php
- [X] T026 Rename tests/Unit/Models/EmployeeTest.php, UserLocationTest.php and TeamMemberServiceTest.php to StaffTest.php, LocationStaffTest.php and StaffLocationServiceTest.php, covering each relation and the `name` and `full_name` fallbacks
- [X] T027 [P] Rename the tests of every action renamed in T017 to T021 under tests/Unit/Actions, and add a test that a cross company account or location fails with ModelNotFoundException
- [X] T028 [P] Update tests/Unit/Permissions/PendingPermissionCheckTest.php and rename EmployeeScopeTest.php to StaffScopeTest.php, covering an account without staff and a paused assignment giving no location scope
- [X] T029 [P] Update tests/Unit/Enums/PermissionEnumTest.php, UserActionEnumTest.php, tests/Unit/Actions/CreateCompanyTest.php, CreateDefaultRolesTest.php, tests/Unit/Models/UserTest.php and LogTest.php, and the view model tests under tests/Unit/ViewModels
- [X] T030 Update every test under tests/Feature/Controllers that reads `employee_id` (`grep -rln employee_id tests/Feature`) and run `php artisan test --compact`, which must be green

## Phase 3: User Story 1, the salon manages its staff (P1) MVP

Independent test: add Pam and Jim, reorder them, mark Jim not bookable, deactivate Pam.

- [X] T031 [P] [US1] Write tests/Unit/Actions/UpdateStaffPositionTest.php, ActivateStaffTest.php, DeactivateStaffTest.php and DestroyStaffTest.php (authorization, cross company, logging on the low queue)
- [X] T032 [US1] Add `is_bookable` and the optional fields of FR-002 to app/Actions/CreateStaff.php and app/Actions/UpdateStaffInformation.php, sanitized like the existing fields
- [X] T033 [P] [US1] Create app/Actions/UpdateStaffPosition.php following app/Actions/UpdateServicePosition.php
- [X] T034 [P] [US1] Create app/Actions/ActivateStaff.php and app/Actions/DeactivateStaff.php following ActivateService.php and DeactivateService.php
- [X] T035 [US1] Create app/Actions/DestroyStaff.php (`staff.delete`, deletes the avatar files, cascade removes the rest), adding its UserActionEnum case

## Phase 4: User Story 2, staff work at locations and perform services (P1)

Independent test: assign Jim to Scranton, let him perform "Haircut" there, pause the assignment.

- [X] T036 [P] [US2] Write tests/Unit/Actions/ActivateLocationStaffTest.php and DeactivateLocationStaffTest.php, including that a paused assignment refuses new services
- [X] T037 [US2] Create app/Actions/ActivateLocationStaff.php and app/Actions/DeactivateLocationStaff.php (`role.manage` for the company), adding their UserActionEnum cases

## Phase 5: User Story 3, staff have working hours (P2)

Independent test: give Dwight Monday 09:00 to 12:00 and 13:00 to 18:00 at Scranton.

- [X] T038 [US3] Create database/migrations/2026_10_15_000000_create_staff_working_hours_table.php, app/Models/StaffWorkingHour.php, database/factories/StaffWorkingHourFactory.php and add `workingHours()` to app/Models/Staff.php
- [X] T039 [P] [US3] Write tests/Unit/Models/StaffWorkingHourTest.php and tests/Unit/Actions/CreateStaffWorkingHourTest.php, UpdateStaffWorkingHourTest.php and DestroyStaffWorkingHourTest.php (day "0 (Sunday) to 6 (Saturday)", end after start, overlap refused, unassigned location refused)
- [X] T040 [US3] Create app/Actions/CreateStaffWorkingHour.php, UpdateStaffWorkingHour.php and DestroyStaffWorkingHour.php (`schedule.manage` through `forStaff`, overlap rule of research R6)
- [X] T041 [US3] Make app/Actions/RemoveLocation.php also delete the staff member's working hours at that location, with a test in tests/Unit/Actions/RemoveLocationTest.php

## Phase 6: User Story 4, staff are absent (P2)

Independent test: record Michael absent everywhere for a week, then Angela at Scranton only.

- [X] T042 [US4] Create database/migrations/2026_10_15_000001_create_staff_absences_table.php, app/Models/StaffAbsence.php (datetime casts), database/factories/StaffAbsenceFactory.php and add `absences()` to app/Models/Staff.php
- [X] T043 [P] [US4] Write tests/Unit/Models/StaffAbsenceTest.php and tests/Unit/Actions/CreateStaffAbsenceTest.php, UpdateStaffAbsenceTest.php and DestroyStaffAbsenceTest.php (end after start, null location allowed, unassigned location refused, a Europe/Paris value stored as UTC)
- [X] T044 [US4] Create app/Actions/CreateStaffAbsence.php, UpdateStaffAbsence.php and DestroyStaffAbsence.php (`schedule.manage` through `forStaff`, values stored with `->utc()`, R7)
- [X] T045 [US4] Make app/Actions/RemoveLocation.php also delete the staff member's absences at that location, with a test in tests/Unit/Actions/RemoveLocationTest.php

## Phase 7: Polish

- [X] T046 Run `php artisan jacques:localize` and translate every reported string in each lang/*.json locale, then run `php artisan jacques:localize --check`
- [X] T047 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [X] T048 Run every check in quickstart.md, including the leftover grep

## Dependencies

- Phase 1 comes before Phase 2. Phase 2 blocks every story.
- US1 and US2 depend only on Phase 2. US3 and US4 depend on Phase 2 and are independent of each other, except that T041 and T045 both edit RemoveLocation.php and must not run at the same time.
- Within each story, tests come first, then the actions.
- Parallel examples: T008, T010 and T011 together. T023, T024 and T027 to T029 together. In US1, T031, T033 and T034 together. US3 and US4 by two people.

## Implementation Strategy

MVP is Phases 1 to 3: the replacement with a green suite, then staff management. Each later phase ships on its own, and the full suite stays green after each one.
