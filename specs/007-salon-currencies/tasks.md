# Tasks: Salon Currencies

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/actions.md](contracts/actions.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`; each task writes or updates the test that mirrors its file, with data from The Office.

## Phase 1: Setup (configuration and schema)

- [X] T001 [P] In config/jacques.php add `currencies` (`['CAD', 'USD', 'EUR', 'GBP', 'CHF']`) and `country_currencies` (`'CA' => 'CAD'`, `'US' => 'USD'`, `'FR' => 'EUR'`, `'BE' => 'EUR'`, `'CH' => 'CHF'`), each with a comment block like `country_languages` saying a country not listed takes `CAD`
- [X] T002 [P] In database/migrations/0000_01_01_000000_create_companies_table.php replace the `currency` column with `$table->json('currencies')->nullable()->comment('ordered list of the currencies the salon prices its catalogue in, the first one being the main currency');` after `languages` (edit in place)

## Phase 2: Foundational (blocks every story)

- [X] T003 In app/Models/Company.php replace `@property string|null $currency` with `@property list<string> $currencies`, swap `currency` for `currencies` in `$fillable` and cast `currencies` to `array`; in database/factories/CompanyFactory.php replace `'currency' => 'USD'` with `'currencies' => ['CAD']`; assert the cast in tests/Unit/Models/CompanyTest.php
- [X] T004 Remove the `currency` argument and its write from app/Actions/UpdateCompany.php and from its call in app/Actions/SetUpSalon.php; update tests/Unit/Actions/UpdateCompanyTest.php
- [X] T005 Write `'currencies' => ['CAD']` next to `'languages' => ['en']` in app/Actions/CreateCompany.php; assert it in tests/Unit/Actions/CreateCompanyTest.php
- [X] T006 [P] Add `CompanyCurrenciesUpdated = 'company_currencies_updated'` with description "Changed the currencies of the salon to :currencies" to app/Enums/UserActionEnum.php; assert it in tests/Unit/Enums/UserActionEnumTest.php

## Phase 3: User Story 1, the owner chooses the salon's currencies during onboarding (P1) MVP

**Independent test**: sign up, choose Canada on step 1, add US dollar, submit, and the company `currencies` is `["CAD", "USD"]`.

- [X] T007 [US1] Create app/Actions/UpdateCompanyCurrencies.php (`author: User`, `company: Company`, `currencies: list<string>`) as a copy of app/Actions/UpdateCompanyLanguages.php: class docblock of one or two sentences, `PermissionEnum::CompanyManage`, `InvalidArgumentException` for an empty list ("A company needs at least one currency"), a code not in `config('jacques.currencies')` ("{code} is not a currency the catalogue can be priced in") or a duplicate ("A currency can only be listed once"), the list written as given, `LogUserAction` with `CompanyCurrenciesUpdated` and `['currencies' => implode(', ', $currencies)]` on the `low` queue; with tests/Unit/Actions/UpdateCompanyCurrenciesTest.php covering order kept, each refusal, a non-owner refused, and the log pushed on `low`
- [X] T008 [US1] Add `private readonly array $currencies` (`@param list<string>`) after `languages` in app/Actions/SetUpSalon.php and run `UpdateCompanyCurrencies` inside the transaction after `updateLanguages()`; mention the currencies in its class docblock; update tests/Unit/Actions/SetUpSalonTest.php
- [X] T009 [P] [US1] In app/ViewModels/Onboarding/LocationViewModel.php add `currencies` to `form()` (`old('currencies')`, else `company->currencies`, else `['CAD']`), `currencies()` returning per code of `config('jacques.currencies')` its `code` and `label` through `__()` ("Canadian dollar", "US dollar", "Euro", "Pound sterling", "Swiss franc"), `currenciesByCountry()` returning for every code of `countries()` its `country_currencies` value, else `'CAD'`, and `currencyCounters()` returning for 1 to the number of offered currencies `trans_choice(':count currency|:count currencies', $count)`; cover them in tests/Unit/ViewModels/Onboarding/LocationViewModelTest.php
- [X] T010 [US1] In app/Http/Controllers/App/Onboarding/LocationController.php validate `currencies` `['required', 'array', 'min:1']` and `currencies.*` `['required', 'string', 'distinct', Rule::in(config('jacques.currencies'))]` with the message "Choose at least one currency." for `currencies.required` and `currencies.min`, and pass `currencies: array_values($validated['currencies'])` to `SetUpSalon`
- [X] T011 [US1] In resources/views/app/onboarding/location.blade.php add, after the language block, the block "Currencies accepted at the salon" built like it: required mark, counter from `currencyCounters()` with "Keep at least one." when one remains, a `role="group"` labelled by the label, one `button type="button"` per currency with `:aria-pressed` and the check box, the text `{label} · {code}` with no flag and no symbol, a hidden `currencies[]` input per selected code in selection order, the help "Every price in your catalogue is written in each of these currencies.", and `x-error` for `currencies`; the last selected currency cannot be deselected; `chooseCountry()` also sets the selection to `[currenciesByCountry()[country]]`
- [X] T012 [US1] In tests/Feature/Controllers/App/Onboarding/LocationControllerTest.php cover: currencies saved in the submitted order; no currency refused with "Choose at least one currency."; an unknown code and a duplicate refused; "Edit the salon" shows the saved currencies selected

## Phase 4: User Story 2, the catalogue is priced in the salon's currencies (P1)

**Independent test**: with `["CAD", "USD"]`, create a service with a price in both currencies, a CAD price alone is refused, and a location replacing USD only still resolves CAD from the service.

- [X] T013 [P] [US2] In database/migrations/2026_10_14_000001_create_services_table.php replace `price` with `$table->json('prices')->comment('price in each salon currency, in its smallest unit, keyed by currency code');` and in database/migrations/2026_10_14_000003_create_location_services_table.php replace `price` with `$table->json('prices')->nullable()->comment('prices this location replaces, in the smallest unit of each currency, keyed by currency code, null when it follows the service');` (edit in place)
- [X] T014 [US2] In app/Models/Service.php change `@property int $price` to `@property array<string, int> $prices`, swap `price` for `prices` in `$fillable` and casts (`array`), use `prices` in the `locations()` pivot, and add `missingCurrencies(): array` returning the company currencies with no key in `prices`, in company order; in app/Models/LocationService.php change `price` to `@property array<string, int>|null $prices` the same way and replace `resolvedPrice()` with `resolvedPrice(string $currency): ?int` (`$this->prices[$currency] ?? $this->service->prices[$currency] ?? null`), updating its docblock; use `prices` in the `services()` pivot of app/Models/Location.php; write `'prices' => ['CAD' => 3500]` in database/factories/ServiceFactory.php and `'prices' => null` in database/factories/LocationServiceFactory.php; cover both methods in tests/Unit/Models/ServiceTest.php and tests/Unit/Models/LocationServiceTest.php, a key of a removed currency never returned as missing
- [X] T015 [US2] In app/Actions/CreateService.php and app/Actions/UpdateService.php replace `int $price` with `array<string, int> $prices`; throw `{code} is not a currency of the company` for a key outside `company->currencies`, `A price is needed in {code}` for a company currency without a key, and `A price cannot be negative` for any amount below 0; on update merge the given prices over the stored ones so keys of removed currencies stay; update tests/Unit/Actions/CreateServiceTest.php and tests/Unit/Actions/UpdateServiceTest.php with each refusal
- [X] T016 [US2] In app/Actions/CreateLocationService.php and app/Actions/UpdateLocationService.php replace `?int $price = null` with `array<string, int> $prices = []`; throw `{code} is not a currency of the company` and `A price cannot be negative`; store `[]` as null; on update merge over the stored keys of removed currencies; update both class docblocks and tests/Unit/Actions/CreateLocationServiceTest.php and tests/Unit/Actions/UpdateLocationServiceTest.php
- [X] T017 [US2] In app/Actions/CreateDefaultCatalogue.php write `'prices' => ['CAD' => $service['price']]`; in app/Actions/OfferServicesAtLocation.php write `'prices' => null`; update tests/Unit/Actions/CreateDefaultCatalogueTest.php and tests/Unit/Actions/OfferServicesAtLocationTest.php
- [X] T018 [US2] Replace every remaining `price` read or write found by `grep -rn "price" app database tests`, except the default catalogue amounts, then run `php artisan test --compact` until green

## Phase 5: User Story 3, currencies change after onboarding (P2)

**Independent test**: add US dollar to a Canadian dollar salon with services, check nothing changed and USD is missing; remove it, confirm the dialog, add it back, and the USD prices return.

- [X] T019 [P] [US3] Add `currenciesWithPrices(): array` to app/ViewModels/Onboarding/LocationViewModel.php returning each company currency that holds a key in the `prices` of at least one service or location service of the company, with its `code` and `label`; cover it in tests/Unit/ViewModels/Onboarding/LocationViewModelTest.php
- [X] T020 [US3] In resources/views/app/onboarding/location.blade.php add `removedCurrencies()` beside `removedLanguages()`, open the existing dialog when either is not empty, retitle it "Change the salon?" with the action "Change the salon", and show one sentence per kind with `x-show`: the existing languages sentence, and "Your catalogue has prices in: {labels}. Their prices stay saved, and come back if you add the currency again."
- [X] T021 [US3] In tests/Unit/Actions/UpdateServiceTest.php and tests/Unit/Actions/SetUpSalonTest.php cover: adding a currency leaves every price unchanged and `missingCurrencies()` returns it; removing it then adding it back restores its prices; updating a service after a currency was removed keeps that currency's key

## Phase 6: Polish

- [X] T022 Amend D2 in docs/specs/decisions.md from "sa devise" to "ses devises", saying each price exists once per currency and is never converted
- [X] T023 Run `php artisan jacques:localize` and translate every reported string in all lang/*.json locales
- [X] T024 Run `vendor/bin/pint -q`, `vendor/bin/phpstan analyse --no-progress -q`, `php artisan test --compact` and `php artisan migrate:fresh --seed -q`; fix every finding
- [ ] T025 Run the quickstart.md scenarios on desktop, tablet and phone width, light and dark, keyboard only and with a screen reader

## Dependencies

- Phase 1, then Phase 2: T003 needs T002; T004 and T005 need T003.
- US1 needs Phase 2. T007 needs T006. T008 needs T007. T010 needs T008. T011 needs T009. T012 needs T010 and T011.
- US2 needs Phase 2, not US1. T014 needs T013. T015, T016 and T017 need T014. T018 needs T015 to T017.
- US3 needs US1 and US2. T020 needs T019. T021 needs T015.
- Phase 6 comes last.

## Parallel examples

- Phase 1: T001 with T002. Phase 2: T006 with T003.
- US1: T009 with T007.
- US2: T013 alongside US1; T015, T016 and T017 once T014 is done.
- Across stories: US1 and US2 run side by side after Phase 2.

## Strategy

MVP is Phase 1, Phase 2 and US1: the salon chooses its currencies on step 1. US2 makes every price one amount per currency, and US3 handles adding and removing a currency. US2 changes the schema of prices, so its tasks land together in one commit. Phase 6 runs before each merge.
