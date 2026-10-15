# Tasks: Default Service Catalogue

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/actions.md](contracts/actions.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`; each task writes or updates the test that mirrors its file, with data from The Office.

## Phase 1: Setup (schema)

- [X] T001 After `languages` in database/migrations/0000_01_01_000000_create_companies_table.php add `$table->datetime('default_catalogue_created_at')->nullable()->comment('when the default catalogue was copied into the company, null until then');` (edit in place)

## Phase 2: Foundational (blocks every story)

- [X] T002 In app/Models/Company.php add `@property Carbon|null $default_catalogue_created_at`, the column in `$fillable`, and the `datetime` cast; leave database/factories/CompanyFactory.php without it (null); assert the cast in tests/Unit/Models/CompanyTest.php

## Phase 3: User Story 1, a new salon starts with a realistic catalogue (P1) MVP

**Independent test**: create a company with `CreateCompany`, and it has 6 categories, 17 active services and 20 steps, in the order of the spec's Default catalogue table.

- [X] T003 [US1] Create app/Actions/CreateDefaultCatalogue.php (`company: Company`, `execute(): void`), class docblock of one or two sentences; in `DB::transaction` reload the company with `lockForUpdate()`, return when `default_catalogue_created_at` is not null, then for each category of the spec's table in order create a `ServiceCategory` (`position` 1 to 6, `description` null) and its services (`price` in cents such as `3500`, `is_active` true, `position` from 1 in the category, `description` null), each simple service with one `ServiceStep` (`position` 1, `active_duration` its minutes, `processing_duration` 0), Full Color with steps 30 + 30 then 30, Highlights 45 + 30 then 30, Deep Conditioning 10 + 15 then 10; set `default_catalogue_created_at` to `now()`; no author, no permission check, no `LogUserAction`; for now write names as `['en' => __('...')]` with a literal string per name
- [X] T004 [US1] Write tests/Unit/Actions/CreateDefaultCatalogueTest.php: 6 categories, 17 services, 20 steps; order and prices of the table; every service's steps total its duration (Full Color 90, Highlights 105, Deep Conditioning 35, Consultation 15 at price 0); a second run creates nothing; a deleted default and an edited price stay as the owner left them after a second run; the date is set; another company (Dunder Mifflin and Vance Refrigeration) is untouched
- [X] T005 [US1] Create app/Jobs/CreateDefaultServices.php implementing `ShouldQueue` with `Queueable`, `public int $companyId`, `public int $tries = 3`, `public int $backoff = 10`, class docblock saying it copies the default catalogue on the queue so signing up stays fast, and `handle()` running `CreateDefaultCatalogue` on `Company::query()->findOrFail($this->companyId)`; with tests/Unit/Jobs/CreateDefaultServicesTest.php asserting the records exist after `handle()`
- [X] T006 [US1] In app/Actions/CreateCompany.php, after the transaction, dispatch `CreateDefaultServices::dispatch(companyId: $this->company->id)->onQueue('high')->afterCommit()`; in tests/Unit/Actions/CreateCompanyTest.php assert `Queue::assertPushedOn('high', CreateDefaultServices::class)` with the company id

## Phase 4: User Story 2, the default texts speak the salon's languages (P1)

**Independent test**: run `CreateDefaultCatalogue`, and every category and service `name` holds non-empty `en`, `fr`, `de` and `es` keys.

- [X] T007 [US2] In app/Actions/CreateDefaultCatalogue.php build each name by calling `__('<name>', [], $locale)` for every key of `config('jacques.locales')` and storing it under `Str::before($locale, '_')`; keep every `__()` argument a literal so `jacques:localize` finds it
- [X] T008 [US2] Run `php artisan jacques:localize` and translate the 23 reported names in lang/fr_FR.json, lang/de_DE.json and lang/es_ES.json (never edit keys by hand)
- [X] T009 [US2] In tests/Unit/Actions/CreateDefaultCatalogueTest.php assert every name has the four keys, "Women's Haircut" reads its lang/fr_FR.json translation under `fr`, and a company with `languages` `['en', 'pt']` still gets the same four keys and no `pt`

## Phase 5: User Story 3, the first location offers the default services (P1)

**Independent test**: run `SetUpSalon` for a company with the default catalogue, and its headquarters offers 17 active services with a null price; a second location offers nothing.

- [X] T010 [P] [US3] Create app/Actions/OfferServicesAtLocation.php (`location: Location`, `execute(): void`), class docblock of one or two sentences; create a `LocationService` (`price` null, `is_active` true) for every service of `location->company_id` with `is_active` true that the location does not offer yet; with tests/Unit/Actions/OfferServicesAtLocationTest.php covering: all active services offered, a deactivated one skipped, an already offered one not duplicated, services of another company never offered
- [X] T011 [US3] At the end of the transaction in app/Actions/CreateDefaultCatalogue.php run `OfferServicesAtLocation` on the company's location with `is_headquarters` true when one exists; in tests/Unit/Actions/CreateDefaultCatalogueTest.php cover with and without a headquarters, and a non headquarters location offered nothing
- [X] T012 [US3] In `saveFirstLocation()` of app/Actions/SetUpSalon.php keep the location returned by `CreateLocation` and run `OfferServicesAtLocation` on it; the update branch does not; in tests/Unit/Actions/SetUpSalonTest.php cover: catalogue before step 1 gives 17 offers; step 1 submitted twice gives 17 offers, not 34; step 1 before the catalogue then `CreateDefaultCatalogue` gives 17 offers; a deleted default before step 1 is not offered; a location created afterwards with `CreateLocation` offers nothing

## Phase 6: Polish

- [X] T013 Run `vendor/bin/pint -q`, `vendor/bin/phpstan analyse --no-progress -q`, `php artisan jacques:localize --check`, `php artisan test --compact` and `php artisan migrate:fresh --seed -q`; fix every finding
- [X] T014 Run the quickstart.md scenarios

## Dependencies

- T001, then T002, then every story.
- US1: T003, then T004 and T005; T006 needs T005.
- US2 needs T003: T007, then T008, then T009.
- US3 needs T003: T010 can start after T002; T011 and T012 need T010.
- Phase 6 comes last.

## Parallel examples

- T010 with T003, once T002 is done.
- T005 with T004; T012 with T011.
- US2 and US3 side by side once T003 is done; T007 and T011 both edit app/Actions/CreateDefaultCatalogue.php, so not together.

## Strategy

MVP is Phases 1 to 3: every new company gets the catalogue in English. US2 fills the four locales, US3 offers it at the first location. Phase 6 runs before the merge.
