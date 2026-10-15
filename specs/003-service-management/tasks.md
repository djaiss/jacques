# Tasks: Service Management

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`; each task writes the test that mirrors its file, with data from The Office. Every action follows `CreateLocation` (authorize, sanitize, validate, write, `LogUserAction` on `low`) and its test checks the refusal and the other company case (`ModelNotFoundException`).

## Phase 1: Setup (schema)

- [ ] T001 [P] Create database/migrations/2026_10_14_000000_create_service_categories_table.php per data-model.md: `company_id` cascade, `name` "required, unique per company", nullable `description`, `position`; foreign keys at the bottom, `->comment()` on every column, `up()` only
- [ ] T002 [P] Create database/migrations/2026_10_14_000001_create_services_table.php per data-model.md: `service_category_id` "nullable, null on delete", `name` unique per company, `price` unsigned integer, `is_active` default true, `position`
- [ ] T003 [P] Create database/migrations/2026_10_14_000002_create_service_steps_table.php: `service_id` cascade, `position` "unique per service", `active_duration` unsigned integer, `processing_duration` unsigned integer default 0
- [ ] T004 [P] Create database/migrations/2026_10_14_000003_create_location_services_table.php: `location_id` and `service_id` cascade, unique on the pair, nullable `price`, `is_active` default true
- [ ] T005 [P] Create database/migrations/2026_10_14_000004_create_team_member_services_table.php: `user_id` and `location_service_id` cascade, unique on the pair

## Phase 2: Foundational (blocks every story)

- [ ] T006 [P] Create app/Models/ServiceCategory.php (`company()`, `services()` ordered by position), database/factories/ServiceCategoryFactory.php and tests/Unit/Models/ServiceCategoryTest.php
- [ ] T007 [P] Create app/Models/Service.php (`company()`, `category()`, `steps()` ordered by position, `locationServices()`, `locations()`, `duration()` accessor summing active and processing durations, `is_active` cast), database/factories/ServiceFactory.php with one step by default, and tests/Unit/Models/ServiceTest.php
- [ ] T008 [P] Create app/Models/ServiceStep.php (`service()`), database/factories/ServiceStepFactory.php and tests/Unit/Models/ServiceStepTest.php
- [ ] T009 [P] Create app/Models/LocationService.php (`location()`, `service()`, `users()` through `team_member_services`, `resolvedPrice()` returning the override or the service price), database/factories/LocationServiceFactory.php and tests/Unit/Models/LocationServiceTest.php
- [ ] T010 [P] Create app/Models/TeamMemberService.php (pivot, mirrors app/Models/UserLocation.php), database/factories/TeamMemberServiceFactory.php and tests/Unit/Models/TeamMemberServiceTest.php
- [ ] T011 Add `serviceCategories()` and `services()` to app/Models/Company.php, `locationServices()` and `services()` to app/Models/Location.php, `locationServices()` to app/Models/User.php; update tests/Unit/Models/CompanyTest.php, LocationTest.php and UserTest.php
- [ ] T012 Add one case per new action to app/Enums/UserActionEnum.php (service_category_created, _updated, _moved, _deleted; service_created, _updated, _moved, _deactivated, _activated, _deleted; location_service_created, _updated, _deleted; team_member_service_assigned, _removed); update tests/Unit/Enums/UserActionEnumTest.php

## Phase 3: User Story 1, the salon builds its catalogue (P1) MVP

**Independent test**: Michael creates "Cuts" and "Colours", three services, reorders them and deactivates one; Dwight (Manager) is refused every write.

- [ ] T013 [P] [US1] Create app/Actions/CreateServiceCategory.php and app/Actions/UpdateServiceCategory.php (`ServiceManage` with `forCompany()`, name required and unique per company, new category last) with their tests in tests/Unit/Actions/
- [ ] T014 [P] [US1] Create app/Actions/UpdateServiceCategoryPosition.php (move one category, shift its siblings in one transaction) with tests/Unit/Actions/UpdateServiceCategoryPositionTest.php
- [ ] T015 [P] [US1] Create app/Actions/DestroyServiceCategory.php; its test proves the services remain with a null category
- [ ] T016 [US1] Create app/Actions/CreateService.php and app/Actions/UpdateService.php taking an ordered list of steps (at least one, `active_duration` "above zero", `processing_duration` "zero or more"; update replaces the steps), `price` "zero or more", category of the same company, new service last in its category; tests prove "Colour" with 20, 30 and 15 lasts 65 minutes
- [ ] T017 [P] [US1] Create app/Actions/UpdateServicePosition.php (move within its category, services without category form one group) with its test
- [ ] T018 [P] [US1] Create app/Actions/DeactivateService.php and app/Actions/ActivateService.php, mirroring app/Actions/ArchiveLocation.php and RestoreLocation.php, with their tests
- [ ] T019 [P] [US1] Create app/Actions/DestroyService.php (deletes steps, offers and who performs it through the cascades) with its test

## Phase 4: User Story 2, each location offers its own selection (P1)

**Independent test**: "Haircut" at 35.00; Scranton overrides 40.00, Stamford has none; the company price moves to 38.00 and only Stamford follows.

- [ ] T020 [US2] Create app/Actions/CreateLocationService.php (`ServiceManage` with `forLocation()`, same company, active service only, not already offered, nullable `price` "zero or more") with its test
- [ ] T021 [P] [US2] Create app/Actions/UpdateLocationService.php (price override, cleared with null, and `is_active`) with its test, including Dwight refused on Stamford
- [ ] T022 [P] [US2] Create app/Actions/DestroyLocationService.php with its test proving the team member services there are deleted

## Phase 5: User Story 3, team members perform services at locations (P2)

**Independent test**: Jim performs "Haircut" at Scranton; at Stamford, where he is not assigned, it is refused.

- [ ] T023 [US3] Create app/Actions/AssignTeamMemberService.php and app/Actions/RemoveTeamMemberService.php, mirroring app/Actions/AssignRole.php and RemoveRole.php (`ServiceManage` with `forLocation()`, user of the same company and assigned to the location, active location service) with their tests
- [ ] T024 [US3] Delete the person's team member services at that location in app/Actions/RemoveLocation.php; update tests/Unit/Actions/RemoveLocationTest.php

## Phase 6: Polish

- [ ] T025 Run `php artisan jacques:localize` and translate every reported string in all lang/*.json locales
- [ ] T026 Run `vendor/bin/pint -q`, `vendor/bin/phpstan analyse --no-progress -q`, `php artisan migrate:fresh --seed --quiet` and `php artisan test --compact`; fix every finding
- [ ] T027 Check every row of the quickstart.md scenario table is covered by a test

## Dependencies

- Phase 1, then Phase 2 (T006 to T010 before T011; T012 before any action).
- US1 needs Phase 2. US2 needs T016 (services to offer). US3 needs T020 (offers to perform).
- T024 needs T023. Phase 6 comes last.

## Parallel examples

- Phase 1: T001 to T005. Phase 2: T006 to T010.
- US1: T013, T014 and T015, then T017, T018 and T019 after T016.
- US2: T021 with T022 after T020.

## Strategy

MVP is US1: a company holds a working catalogue with steps. US2 makes it available per location with its price, US3 says who performs it, which the availability engine will need.
