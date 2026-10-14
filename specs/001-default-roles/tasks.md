# Tasks: Default Roles

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`; each task updates the test that mirrors its file.

## Phase 1: Setup (schema)

- [X] T001 [P] Remove `owner_user_id` from database/migrations/0000_01_01_000000_create_companies_table.php and its foreign key from database/migrations/0001_01_01_000000_create_users_table.php
- [X] T002 [P] Add nullable `description` text column to database/migrations/2026_08_01_000001_create_roles_table.php
- [X] T003 [P] Create database/migrations/2026_08_01_000005_create_user_locations_table.php: `user_id`, `location_id`, timestamps, unique on the pair, both foreign keys cascade, declared at the bottom (no `constrained()`, `up()` only)

## Phase 2: Foundational (blocks every story)

- [X] T004 [P] Add `Location` (`location`) between `Self` and `Company` in app/Enums/ScopeEnum.php, labels "Their locations" and "Locations"; update tests/Unit/Enums/ScopeEnumTest.php
- [X] T005 [P] Replace the groups in app/Enums/PermissionGroupEnum.php with Administration, Locations, Team, Services, Clients, Appointments, Reports, each with label and note; update tests/Unit/Enums/PermissionGroupEnumTest.php
- [X] T006 Replace the cases of app/Enums/PermissionEnum.php with the 16 permissions of data-model.md (values, scopes, groups, labels); replace `targetsEmployee()` with a `subject()` method returning company, location or employee for the permissions that can be checked today; update tests/Unit/Enums/PermissionEnumTest.php
- [X] T007 [P] Create app/Models/UserLocation.php (pivot with timestamps, mirrors app/Models/UserRole.php), database/factories/UserLocationFactory.php and tests/Unit/Models/UserLocationTest.php
- [X] T008 Add `locations()` (belongsToMany through `user_locations`) and `isOwner()` to app/Models/User.php; add `users()` to app/Models/Location.php; update tests/Unit/Models/UserTest.php and tests/Unit/Models/LocationTest.php
- [X] T009 Remove `owner()` and `owner_user_id` from app/Models/Company.php and database/factories/CompanyFactory.php; update tests/Unit/Models/CompanyTest.php
- [X] T010 Replace the four slug constants with `OWNER`, `MANAGER`, `STAFF`, `RECEPTIONIST` and add the `description` property in app/Models/Role.php and database/factories/RoleFactory.php; update tests/Unit/Models/RoleTest.php
- [X] T011 In app/Permissions/PendingPermissionCheck.php remove the owner bypass, add `forLocation(Location)` (company scope, or location scope when the user is assigned to it), and let `forEmployee()` accept location scope when the employee's user shares a location with the user; update tests/Unit/Permissions/PendingPermissionCheckTest.php
- [X] T012 Apply the same three scopes to app/Permissions/EmployeeScope.php and drop its owner bypass; update tests/Unit/Permissions/EmployeeScopeTest.php
- [X] T013 Update the `grant()` and default role helpers in tests/TestCase.php to the new slugs and scopes

## Phase 3: User Story 1, every company starts with the four roles (P1) MVP

**Independent test**: create a company; it holds Owner, Manager, Staff, Receptionist and its creator holds Owner.

- [X] T014 [US1] Rewrite app/Actions/CreateDefaultRoles.php with the four roles, names and descriptions through `__()`, and the grants of data-model.md; Owner gets `is_editable = false`; update tests/Unit/Actions/CreateDefaultRolesTest.php
- [X] T015 [US1] In app/Actions/CreateCompany.php stop setting `owner_user_id` and give the creator the Owner role; update tests/Unit/Actions/CreateCompanyTest.php and tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php
- [X] T016 [P] [US1] Replace the `owner_user_id` checks with `User::isOwner()` in app/Actions/DestroyCompany.php, app/Actions/DestroyUser.php and app/Actions/UpdateUserInformation.php; update their tests in tests/Unit/Actions/
- [X] T017 [P] [US1] Let app/Actions/CreateRole.php and app/Actions/UpdateRole.php accept a `description` and the `location` scope; update tests/Unit/Actions/CreateRoleTest.php and tests/Unit/Actions/UpdateRoleTest.php
- [X] T018 [US1] Pass the description and the three scopes through app/Http/Controllers/App/Settings/Administration/RoleController.php and DuplicateRoleController.php; update their feature tests
- [X] T019 [US1] Show the description and one radio group per permission offering only its allowed scopes in app/ViewModels/Settings/Administration/RolesViewModel.php, resources/views/app/settings/administration/roles/_permissions.blade.php and _roles.blade.php; update tests/Unit/ViewModels/Settings/Administration/RolesViewModelTest.php

## Phase 4: User Story 2, each role grants exactly what it describes (P1)

**Independent test**: for each default role, every capability of the FR-004 matrix that exists today is allowed or refused as listed.

- [X] T020 [P] [US2] Check `location.manage` at company scope in app/Actions/CreateLocation.php and app/Actions/DestroyLocation.php; update their tests
- [X] T021 [P] [US2] Check `location.manage` with `forLocation()` in app/Actions/UpdateLocation.php, app/Actions/ArchiveLocation.php and app/Actions/RestoreLocation.php; cover a Manager on an assigned and an unassigned location in their tests
- [X] T022 [P] [US2] Move app/Actions/UpdateEmployeeInformation.php, UpdateEmployeeAvatar.php and DestroyEmployeeAvatar.php to `employee.update`, and app/Actions/CreateEmployee.php to `employee.create` at company scope; update their tests
- [X] T023 [P] [US2] Check `location.manage` per row in app/Http/Controllers/App/Settings/Administration/LocationController.php and app/ViewModels/Settings/Administration/LocationsViewModel.php, listing only the locations the user may manage; update their tests
- [X] T024 [P] [US2] Show the Locations and Roles rows from `location.manage` and `role.manage` in app/ViewModels/Settings/SettingsViewModel.php; update tests/Unit/ViewModels/Settings/SettingsViewModelTest.php and tests/Feature/Controllers/App/Settings/SettingsControllerTest.php
- [X] T025 [US2] Add tests/Unit/Actions/CreateDefaultRolesMatrixTest.php asserting every allowed and refused cell of data-model.md for the four default roles, and the union for a user holding Staff and Receptionist

## Phase 5: User Story 3, the Owner hands out roles and locations (P2)

**Independent test**: as Owner, assign a colleague to one location and give them Manager; they act on that location only.

- [X] T026 [P] [US3] Create app/Actions/AssignLocation.php and app/Actions/RemoveLocation.php (check `role.manage`, user and location in the same company, log the change) with tests/Unit/Actions/AssignLocationTest.php and tests/Unit/Actions/RemoveLocationTest.php
- [X] T027 [US3] Add `location_assigned` and `location_removed` to app/Enums/UserActionEnum.php; update its test
- [X] T028 [US3] Create app/Http/Controllers/App/Settings/Administration/LocationPeopleController.php (`create`, `destroy`), its routes `settings.locationPeople.create` and `settings.locationPeople.destroy` in routes/web.php, and tests/Feature/Controllers/App/Settings/Administration/LocationPeopleControllerTest.php
- [X] T029 [US3] Create resources/views/app/settings/administration/locations/_location-people.blade.php, included from _edit-location.blade.php, with a confirmation before removing somebody; feed it from LocationsViewModel and test it
- [X] T030 [P] [US3] Refuse in app/Actions/RemoveRole.php to take Owner from the last active holder, and in app/Actions/DestroyUser.php to delete them; update both tests
- [X] T031 [US3] Show why Owner cannot be taken away in resources/views/app/settings/administration/roles/_people.blade.php through RolesViewModel; update its test

## Phase 6: Polish

- [X] T032 Run `php artisan jacques:localize` and translate every reported string in all lang/*.json locales
- [X] T033 [P] Remove every remaining reference to the old slugs, `employee.view_private`, `employee.update_private` and `owner_user_id` (grep app, database, resources, tests)
- [X] T034 Run `vendor/bin/pint -q`, `vendor/bin/phpstan analyse --no-progress -q` and `php artisan test --compact`; fix every finding
- [ ] T035 Run the quickstart.md scenarios on desktop and phone width, keyboard only

## Dependencies

- Phase 1, then Phase 2 (T006 before T011 and T012; T007 and T008 before T011).
- US1 needs Phase 2. US2 needs T014 (default grants). US3 needs Phase 2 and T014.
- US2 and US3 can run in parallel once US1 is done. Phase 6 comes last.

## Parallel examples

- Phase 1: T001, T002, T003.
- US1: T016 with T017.
- US2: T020 to T024.
- US3: T026 with T030.

## Strategy

MVP is US1: new companies get the right roles and the editor works. US2 makes the existing screens obey them, US3 makes location scope usable from the interface.
