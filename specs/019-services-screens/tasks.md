# Tasks: Services screens

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Add every route of contracts/routes.md to routes/web.php in a new `Route::middleware(['auth', 'set.locale', 'welcome.screen'])` group, `services/new` before `services/{service}`, `whereNumber` on `service`, `location` and `category`; create empty controllers under app/Http/Controllers/App/Services/ so `php artisan route:list --path=service` lists 15 routes

## Phase 2: Foundational

- [X] T002 Create app/Actions/SyncServiceOffers.php (author, service, `offers` as location id => list of staff ids): authorize `ServiceManage` for the company; for each location of the company, a key present creates the offer with CreateLocationService or reactivates it with UpdateLocationService keeping its prices, then assigns the listed staff with AssignStaffLocationService and removes the others with RemoveStaffLocationService; a key absent pauses an existing offer (`isActive: false`, prices kept, research R3) and removes its staff; a location or staff id of another company, or staff not active at that location, throws ModelNotFoundException; one transaction
- [X] T003 Write tests/Unit/Actions/SyncServiceOffersTest.php: Balayage offered at Scranton by Pam and Phyllis; unticking Stamford pauses it and keeps its CAD override; Jim not active at Nashua is refused; another company's location is refused; a user without `ServiceManage` is refused
- [X] T004 [P] Create app/ViewModels/Services/SampleServiceStatistics.php from the design's figures, seeded with `crc32` of the service id: `stats()` (Bookings, Earned, Earned per booked hour, Clients who came back, each value, trend and hint), `weeks()` (12 bars with count, label W1 to W12, height), `insights()` (Growing, Slowing down, Clients come back for it, earns less or more per hour, plus "Cannot be booked at :salon" from real offers), `bookingsPerPerson(staff id)`; class docblock saying it is replaced when appointments exist (research R6); test in tests/Unit/ViewModels/Services/SampleServiceStatisticsTest.php that two calls give the same figures and two services differ

## Phase 3: User Story 1, read the catalogue (P1)

Independent test: 17 services, 6 categories, 3 salons; search "cut"; pick Stamford; rows and warnings match the spec.

- [X] T005 [US1] Create app/ViewModels/Services/ServicesViewModel.php (user, ?Location, search): `lead()` (all salons or "N of M services are offered at :salon, by :count people." through `trans_choice`), `salons()` (All salons then each active location with service and people counts, url, isCurrent), `categories()` (key, name, hue, count, rows; categories without a match dropped while searching), `uncategorized()`, `peopleHeading()`, `noResults()`, `canManage()`; each row: name in the person's locale through `CatalogueText::resolve`, steps count when above one, duration, one price per company currency with symbol (reuse the currency table of app/ViewModels/Onboarding/ServicesViewModel.php by moving it to app/Helpers/CurrencySymbols.php), salons label, people (initials, hue, at most 5 then "+N"), the warnings of US1, show url; archived services excluded; eager load to keep the query count fixed
- [X] T006 [US1] Write tests/Unit/ViewModels/Services/ServicesViewModelTest.php: counts, lead for all salons and for Stamford, search, archived excluded, "Not offered anywhere yet", "Nobody does it yet", another company's services absent, query count unchanged between 3 and 30 services
- [X] T007 [US1] Implement `index` in ServiceController.php and ServiceLocationController.php: authorize `ServiceView` anywhere (404 otherwise), location found in the company, `q` trimmed, view `app.services.index`
- [X] T008 [US1] Create resources/views/app/services/index.blade.php and _row.blade.php to the design: breadcrumb (company / Services), title, lead, search form (GET, `q`), Add a category and Add a service only when `canManage()`, sticky navigation (SALONS, CATEGORIES anchors) above the cards under `lg`, category cards with count tile, column header, rows as links with chevron, no result card, empty catalogue sentence
- [X] T009 [US1] Pass `servicesUrl` from app/View/Components/TopBarLayout.php (null without `ServiceView` anywhere) through resources/views/layouts/top-bar.blade.php to the Services tile of resources/views/components/top-bar.blade.php; extend tests/Feature/Controllers/App/DashboardControllerTest.php (now two tiles inert for a person without permissions)
- [X] T030 [US1] Write tests/Browser/Services/ServicesTest.php: open Services from the top menu, search "balay" then "zzz", pick Stamford in the navigation and check the address, the lead and the people heading, follow a row to its page
- [X] T010 [US1] Write tests/Feature/Controllers/App/Services/ServiceControllerTest.php (index) and ServiceLocationControllerTest.php: 200 with rows, search, Stamford lead, another company's location 404, no `ServiceView` 404, no add buttons without `ServiceManage`

- [X] T034 [US1] Add the empty state of the design: `isEmpty()`, `canLoadReadyMadeCatalogue()` and the empty lead in ServicesViewModel.php; POST `services/catalogue` (`serviceCatalogues.create`) to app/Http/Controllers/App/Services/ServiceCatalogueController.php running RestoreDefaultCatalogue; the "No services yet" card in index.blade.php with search and navigation hidden
- [X] T035 [US1] Test it: ServicesViewModelTest.php, ServiceControllerTest.php (empty state, no ready-made button once a service exists, even archived), tests/Feature/Controllers/App/Services/ServiceCatalogueControllerTest.php, and tests/Browser/Services/EmptyCatalogueTest.php

## Phase 4: User Story 2, add a service and a category (P1)

Independent test: add Beard Trim in Haircuts, 20 min, CA$ 15, US$ 12, Scranton with Jim; add Barbering.

- [X] T011 [US2] Create app/ViewModels/Services/ServiceFormViewModel.php (user, ?Service, ?ServiceCategory preselected): eyebrow, title, save label, names, descriptions, translation status, category options, prices per currency, steps, processing name (as app/ViewModels/Onboarding/ServicePanel.php), `salons()` (each active location: name, hue, head office, on, people active there with on, count text "Nobody yet" or ":count of :total people", tick everyone label), action url and method, `old()` values winning
- [X] T012 [US2] Create resources/views/app/services/_form.blade.php (Details card with the language switch and the steps editor of resources/views/app/onboarding/_service-panel.blade.php, saved once on submit instead of on change; salons boxes with Alpine ticks and "Tick everyone at :salon" / "Untick everyone") and new.blade.php (breadcrumb ending "New service", Cancel back to the list)
- [X] T013 [US2] Implement ServiceController `new`, CategoryServiceController `new` and ServiceController `create`: authorize `ServiceManage` for the company; validate per data-model.md (`name.{first language}` "required, string, max:255", `name.*` "nullable, string, max:255", `description.*` "nullable, string, max:1000", `category` "nullable, integer", `prices.*` "nullable, decimal:0,2, min:0, max:1000000" after reading a comma as a point, `steps` "required, array, min:1", `steps.*.duration` "required, integer, min:1, max:1440", `steps.*.requires_staff` "required, boolean", `offers` "array", `offers.*` "array", `offers.*.*` "integer"); CreateService then SyncServiceOffers in `DB::transaction`; redirect to `services.show` with "The service is added."
- [X] T014 [P] [US2] Create app/ViewModels/Services/ServiceCategoryFormViewModel.php (names per language, action, method, services of the category for the removal warning) and resources/views/app/services/categories/new.blade.php
- [X] T015 [US2] Implement ServiceCategoryController `new` and `create` (`name.{first language}` "required, string, max:255"; CreateServiceCategory; redirect to `services.index` with "The category is added.")
- [X] T016 [US2] Write tests/Feature/Controllers/App/Services/ServiceControllerTest.php (new, create), CategoryServiceControllerTest.php, ServiceCategoryControllerTest.php (new, create) and tests/Unit/ViewModels/Services/ServiceFormViewModelTest.php: Beard Trim created with Jim at Scranton, missing name 422 back with errors, duration 0 refused, category of another company 404, no `ServiceManage` 404
- [X] T031 [US2] Write tests/Browser/Services/AddServiceCategoryTest.php: from the list, Add a category, save "Barbering", see it in the navigation and as an empty card
- [X] T017 [US2] Write tests/Browser/Services/AddServiceTest.php (also: Add a service from the top of the list, an empty name shows the error): from the Haircuts card, tick Scranton, untick everyone but Jim, save, land on the service page

## Phase 5: User Story 3, read one service (P2)

Independent test: Balayage at Scranton by Pam, at Stamford by nobody; the three boxes read as the spec says.

- [X] T018 [US3] Create app/ViewModels/Services/ServiceViewModel.php: breadcrumb, category eyebrow, name, steps badge, edit url when `canManage()`, archived state with restore url, description or its empty sentence, facts (duration, price per currency, offered at, done by through `trans_choice`), salons (off, empty warning, people with sample counts), and the figures of SampleServiceStatistics
- [X] T019 [US3] Create resources/views/app/services/show.blade.php to the design: header, the note "Sample figures. Real ones arrive with appointments." above the four statistics, Bookings per week bars with `role="img"` and an `aria-label` summary, What stands out, About, Who does it; archived banner with "Restore this service"
- [X] T032 [US3] Write tests/Browser/Services/ServiceTest.php: the service page shows the sample figures note, the three salon boxes, and "Edit this service" only for somebody who may change services
- [X] T020 [US3] Implement ServiceController `show` (`ServiceView` anywhere, service of the company or 404) and tests in ServiceControllerTest.php and tests/Unit/ViewModels/Services/ServiceViewModelTest.php (the three box states, sample note present, another company 404)

## Phase 6: User Story 4, edit, archive and restore (P2)

Independent test: untick Stamford and save; archive; restore.

- [X] T021 [US4] Create resources/views/app/services/edit.blade.php from _form.blade.php with "EDIT SERVICE", "Save changes", breadcrumb Services / name / Edit, and the Archive section with its two step confirmation ("Archive this service", "The service leaves the catalog and nobody can book it. Sure?", "Keep it", "Archive it")
- [X] T022 [US4] Implement ServiceController `edit` and `update` (rules of T013; UpdateService then SyncServiceOffers in one transaction; redirect to `services.show` with "The service is saved.")
- [X] T023 [US4] Implement ServiceArchiveController `create` (DeactivateService, redirect to `services.index`, "The service is archived.") and `destroy` (ActivateService, redirect to `services.show`, "The service is back in the catalog.")
- [X] T024 [US4] Write tests: ServiceControllerTest.php (edit, update, Stamford paused, another company 404, no `ServiceManage` 404), ServiceArchiveControllerTest.php (archive leaves the list, restore brings it back with its offers), and tests/Browser/Services/EditServiceTest.php (switch to French, rename, archive with confirmation, restore)

## Phase 7: User Story 5, rename and remove a category (P3)

- [X] T025 [US5] Create resources/views/app/services/categories/edit.blade.php (rename, and "Remove this category" behind `x-confirm-dialog` naming its services and saying they move to "Without a category"); link each category name of index.blade.php to it when `canManage()`
- [X] T026 [US5] Implement ServiceCategoryController `edit`, `update` (UpdateServiceCategory keeping its descriptions) and `destroy` (DestroyServiceCategory with `withServices: false`); extend ServiceCategoryControllerTest.php (Makeup removed, its two services without a category, another company 404)

- [X] T033 [US5] Write tests/Browser/Services/EditServiceCategoryTest.php: rename a category from its card, then remove it after the confirmation and see its service under "Without a category"

## Phase 8: Polish

- [X] T027 Run `php artisan jacques:localize`, translate every reported string in each lang/*.json locale, then `php artisan jacques:localize --check`
- [X] T028 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q --memory-limit=1G` and fix the findings
- [ ] T029 Run `vendor/bin/pest --parallel --compact --testsuite=Unit,Feature`, the browser tests of tests/Browser/Services, and the manual scenarios of quickstart.md

## Dependencies

- T001 before every controller task; T002 and T003 before T013 and T022; T004 before T018.
- US1 first (the list is where every other page returns). US2 needs US1's list; US4 reuses US2's form; US3 can follow US1 directly; US5 needs T014.
- Parallel examples: T004 with T002; T014 with T011; T018 with T021 once T012 exists; T028 with T027.

## Implementation Strategy

MVP is Phases 1 to 4: a catalogue that can be read and grown. US3 and US4 ship next in the same pull request, since the list rows lead to the service page. US5 can follow alone.
