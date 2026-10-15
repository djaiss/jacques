# Tasks: Onboarding Step 2, Services

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Amend D5 in docs/specs/decisions.md: a step has a name per salon language, a duration, and whether the team member is busy during it; a service keeps at least one busy step (R1)

## Phase 2: Foundational (named steps, relaxed catalogue, page shell)

- [X] T002 Edit database/migrations/2026_10_14_000002_create_service_steps_table.php: replace `active_duration` and `processing_duration` with `name` "json, nullable", `duration` "unsigned int, minutes, at least 1" and `requires_staff` boolean, each with `->comment()`, only `up()`
- [X] T003 Edit app/Models/ServiceStep.php (fillable, casts `name` array, `duration` integer, `requires_staff` boolean, class docblock) and database/factories/ServiceStepFactory.php
- [X] T004 Edit app/Models/Service.php: `duration` sums `steps.duration`; add `busyDuration` and `freeDuration` accessors, `isBookableIn(string $currency)`, and extend `missingLanguages()` to the written description and to step names when the service has more than one step (data-model.md); update its class docblock and database/factories/ServiceFactory.php
- [X] T005 Relax app/Actions/CreateService.php and app/Actions/UpdateService.php: empty names, descriptions and step names are dropped from the json, no language nor currency is required, uniqueness checked only for written names, steps shaped `{name: array<string, string>, duration: int, requires_staff: bool}` with "at least one step, and at least one with `requires_staff`" and duration at least 1, log name falling back to `__('Untitled')` (R2)
- [X] T006 [P] Relax app/Actions/CreateServiceCategory.php and app/Actions/UpdateServiceCategory.php the same way (no required language, uniqueness only for written names, `__('Untitled')` in the log)
- [X] T007 Create app/Helpers/DefaultCatalogue.php holding the example content moved from app/Actions/CreateDefaultCatalogue.php, with steps rewritten as named steps (Full Color: "Color application" 30 busy, "Processing" 30 free, "Rinse & finish" 30 busy; Highlights 45, 30, 30; Deep Conditioning 10, 15, 10 named "Application", "Processing", "Rinse & finish"; one step services unnamed), names through `__()` per interface locale (R1)
- [X] T008 Make app/Actions/CreateDefaultCatalogue.php read app/Helpers/DefaultCatalogue.php and write the new step columns, keeping its once only guard
- [X] T009 [P] Migrate tests/Unit/Models/ServiceTest.php, ServiceStepTest.php, tests/Unit/Actions/CreateServiceTest.php, UpdateServiceTest.php, CreateDefaultCatalogueTest.php, CreateServiceCategoryTest.php and UpdateServiceCategoryTest.php to the new shape, adding cases for missing names and prices, duplicate written names refused, no busy step refused, `busyDuration`, `freeDuration`, `isBookableIn`, and add tests/Unit/Helpers/DefaultCatalogueTest.php
- [X] T010 Create app/ViewModels/Onboarding/ServicesViewModel.php extending StepViewModel, loading categories with services and steps in one eager query, with `languages()`, `currencies()`, `categories()`, `serviceCount()`, `missingPriceCount()`, `footerStatus()` (plural aware through `trans_choice()`), and `removal()` reading the session key `onboarding.removal`
- [X] T011 Make app/Http/Controllers/App/Onboarding/ServiceController.php `index` use ServicesViewModel, and rewrite resources/views/app/onboarding/services.blade.php as the shell of the design: breadcrumb, eyebrow, title, introduction, tracker from _header.blade.php, an Alpine root holding `language`, and resources/views/app/onboarding/_catalogue-footer.blade.php with "I'll set this up later" and "Continue to your team" both submitting the PUT form
- [X] T012 Make app/Actions/CompleteOnboardingStep.php run OfferServicesAtLocation on the headquarters whenever the step is `Services`, even when already completed (R7), with cases in tests/Unit/Actions/CompleteOnboardingStepTest.php
- [X] T013 [P] Write tests/Unit/ViewModels/Onboarding/ServicesViewModelTest.php and update tests/Feature/Controllers/App/Onboarding/ServiceControllerTest.php (index renders, both footer buttons open step 3, a non owner is refused), then run `php artisan test --compact`, which must be green

## Phase 3: User Story 1, the owner adjusts the example catalogue (P1) MVP

Independent test: edit a duration and a price inline, remove a service, undo, reload, continue.

- [X] T014 [P] [US1] Write tests/Unit/Actions/UpdateServiceDurationTest.php, UpdateServicePricesTest.php and RestoreCatalogueSnapshotTest.php, and tests/Unit/Helpers/CatalogueSnapshotTest.php (another company's service fails with ModelNotFoundException, a multi step service refuses a duration, a price "0 or more, in cents", a snapshot restored at its old rank)
- [X] T015 [US1] Create app/Actions/UpdateServiceDuration.php (one step services only, duration at least 1) and app/Actions/UpdateServicePrices.php (`array<string, int|null>`, null removes the price, currency of the salon), with `ServiceDurationUpdated` and `ServicePricesUpdated` in app/Enums/UserActionEnum.php
- [X] T016 [US1] Create app/Helpers/CatalogueSnapshot.php (read only; categories and services with texts, prices, ranks and steps, shaped as data-model.md) and app/Actions/RestoreCatalogueSnapshot.php (recreates the rows, shifts ranks to put them back in place, `RemovalRestored` log case)
- [X] T017 [US1] Add the routes `onboarding.services.services.destroy`, `serviceDurations.update`, `servicePrices.update` and `restoration.create` to routes/web.php per contracts/routes.md, with `onboarding.step:2`
- [X] T018 [US1] Create app/Http/Controllers/App/Onboarding/Catalogue/ServiceController.php `destroy` (snapshot, `DestroyService`, session `onboarding.removal` with "“:name” removed."), ServiceDurationController.php, ServicePriceController.php (`nullable`, `decimal:0,2`, `min:0`, cents with `(int) round($value * 100)`) and RestorationController.php; every mutation forgets `onboarding.removal` and redirects with `status` "Saved"
- [X] T019 [US1] Create resources/views/app/onboarding/_catalogue-category.blade.php (tile, name, count, list) and _service-row.blade.php (name, "No CA$ price" badge, steps badge, duration form or read only total, prices form with the symbol visible and the currency name as `aria-label` and `title`, edit and remove buttons), each form submitting on `change` with `x-target` per contracts/routes.md
- [X] T020 [US1] Show in _catalogue-footer.blade.php the undo sentence with "Undo", the "Saved" status fading after two seconds, and otherwise the plural aware service count
- [X] T021 [US1] Write tests/Feature/Controllers/App/Onboarding/Catalogue/ServiceControllerTest.php (destroy), ServiceDurationControllerTest.php, ServicePriceControllerTest.php and RestorationControllerTest.php, each with another company's service returning 404

## Phase 4: User Story 2, the owner organizes categories (P1)

Independent test: rename "Hair Styling", add "Nails", move "Barbering" up, remove "Hair Treatments".

- [X] T022 [P] [US2] Add `withServices: bool` (default false) to app/Actions/DestroyServiceCategory.php, deleting its services when true, with cases in tests/Unit/Actions/DestroyServiceCategoryTest.php
- [X] T023 [US2] Add the four category routes of contracts/routes.md to routes/web.php and create app/Http/Controllers/App/Onboarding/Catalogue/CategoryController.php (`create` unnamed, `update` with `name.{lang}` "nullable string, max 255", `destroy` with snapshot and "“:name” and its :count services removed." through `trans_choice()`) and CategoryPositionController.php (`direction` in `up`, `down`, through UpdateServiceCategoryPosition)
- [X] T024 [US2] Add to _catalogue-category.blade.php the inline name form, collapse toggle with `aria-expanded`, up and down buttons disabled at the ends, and remove button opening `x-confirm-dialog` only when the category holds services; add "Add a category" to services.blade.php
- [X] T025 [P] [US2] Write tests/Feature/Controllers/App/Onboarding/Catalogue/CategoryControllerTest.php and CategoryPositionControllerTest.php, each with another company's category returning 404

## Phase 5: User Story 3, the owner edits a service and its steps (P1)

Independent test: break "Balayage" into 45 busy, 30 free, 30 busy; the list shows 105 min and "3 steps".

- [X] T026 [US3] Add the routes `onboarding.services.services.create`, `edit` and `update` to routes/web.php and the `create`, `edit` and `update` methods to app/Http/Controllers/App/Onboarding/Catalogue/ServiceController.php, validating the panel form exactly as contracts/routes.md lists it; `create` makes a 30 minute busy step with no price and redirects with the panel open
- [X] T027 [US3] Create app/ViewModels/Onboarding/ServicePanel.php (texts per language, category options, prices with status "1 of 2 missing" or "Set in all 2 currencies", steps, totals, timeline proportions) returned by ServicesViewModel
- [X] T028 [US3] Create resources/views/app/onboarding/_service-panel.blade.php inside `x-side-modal`: name and description per language, category select, prices, single duration with "Break it into steps", or the step list (name, duration, "Needs a team member", up, down, remove, "Add a step" 15 busy), totals and the two row timeline, "Remove this service" and "Done"; Alpine edits the step rows, then the form submits
- [X] T029 [US3] Create resources/views/app/onboarding/_steps-explainer.blade.php, the "Services with steps" card with its busy, free, busy example
- [X] T030 [US3] Extend tests/Feature/Controllers/App/Onboarding/Catalogue/ServiceControllerTest.php: create, edit, update with steps, no busy step refused with an error, duplicate name refused, category of another company refused

## Phase 6: User Story 4, the owner fills in every salon language (P2)

Independent test: with English and French, name a service in English only, pick French, name it, see "All translated".

- [X] T031 [US4] Create app/ViewModels/Onboarding/CatalogueLanguages.php (per language: written fields, total fields, missing count, complete, default flag) and per category and service missing counts in ServicesViewModel, both tested in tests/Unit/ViewModels/Onboarding/
- [X] T032 [US4] Create resources/views/app/onboarding/_catalogue-languages.blade.php (cards as radio tabs, progress bar, "x of n fields translated" through `trans_choice()`), shown only with several languages, and the muted fallback, category count and service marker with its tooltip in the list and the panel chips

## Phase 7: User Story 5, the owner starts over, or postpones (P3)

Independent test: empty the catalogue, undo, empty it again, restore the example, then "I'll set this up later".

- [X] T033 [P] [US5] Create app/Actions/DestroyCatalogue.php and app/Actions/RestoreDefaultCatalogue.php (refused unless the catalogue is empty, reads app/Helpers/DefaultCatalogue.php), log cases `CatalogueDestroyed` and `CatalogueRestored`, with tests in tests/Unit/Actions/
- [X] T034 [US5] Add the two catalogue routes to routes/web.php and create app/Http/Controllers/App/Onboarding/Catalogue/CatalogueController.php (`destroy` with snapshot, `create`), tested in tests/Feature/Controllers/App/Onboarding/Catalogue/CatalogueControllerTest.php
- [X] T035 [US5] Add "Start with an empty catalog" with `x-confirm-dialog` and the empty state ("Your catalog is empty", "Add a category", "Use the example catalog") to services.blade.php

## Phase 8: Polish

- [X] T036 Run `php artisan jacques:localize` and translate every reported string in each lang/*.json locale, then `php artisan jacques:localize --check`
- [X] T037 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [ ] T038 Run every check and the manual walk of quickstart.md, at desktop, tablet and phone widths, keyboard only

## Dependencies

- T001 before Phase 2. Phase 2 blocks every story. US1 provides the snapshot and the footer undo that US2 and US5 reuse.
- US2, US3 and US4 depend only on US1 and may run in parallel, except that T023, T026 and T034 all edit routes/web.php and the Catalogue ServiceController is shared by T018 and T026.
- Parallel examples: T006 and T009 together; T014 with T015; T022 with T025; T033 alongside US3.

## Implementation Strategy

MVP is Phases 1 to 3: named steps with a green suite, then inline editing, removal and undo. Each later phase ships on its own with the suite green.
