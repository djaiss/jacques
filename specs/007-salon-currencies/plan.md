# Implementation Plan: Salon Currencies

**Branch**: `2026-10-14-salon-currencies` | **Date**: 2026-10-14 | **Spec**: [spec.md](spec.md)

## Summary

A company gets an ordered list of currencies, chosen on onboarding step 1 in a block copied from
the language block, preselected by country. A service price becomes one amount per company
currency, and a location may replace any of them. This is the 005 pattern applied to prices.
Decisions are in [research.md](research.md), columns in [data-model.md](data-model.md),
signatures in [contracts/actions.md](contracts/actions.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel

**Primary Dependencies**: none new

**Storage**: SQLite, MySQL and PostgreSQL; three migrations edited in place (pre-production)

**Testing**: PHPUnit, Larastan, Pint

**Target Platform**: web back office

**Project Type**: Laravel web application, Blade and Alpine

**Performance Goals**: reading a price costs no query beyond the service, its location and its company

**Constraints**: a refusal or a record of another company answers as not found; no conversion between currencies

**Scale/Scope**: up to 5 currencies per company, a few dozen services

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: one new action, eight actions changed, the controller only validates |
| II. Simplicity | Pass: JSON columns and casts, two model methods, no helper (R5) |
| III. Tested | Pass: every changed model, action, view model and controller has its test |
| IV. Laravel conventions | Pass: migrations edited in place; `up()` only where this feature writes |
| V. Localized, safe | Pass: currency names through `__()`, the counter through `trans_choice()`, removal confirmed (R7) |
| VI. Accessible | Pass: toggles are buttons with `aria-pressed`, each naming its currency and code (R9) |
| VII. One core | Justified: no API exists yet, see Complexity Tracking |
| VIII. Short record | Pass: every artifact within its cap; spec at 120 lines |
| Invariants | D2 to amend to "its currencies" before merge (spec, Assumptions); D22 holds, nothing is converted or charged |

Re-checked after Phase 1: no change.

## Project Structure

### Documentation (this feature)

```text
specs/007-salon-currencies/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
├── contracts/actions.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
database/migrations/   companies (currency out, currencies in), services and location_services
                       (price out, prices in)
database/factories/    CompanyFactory, ServiceFactory, LocationServiceFactory
config/jacques.php     currencies, country_currencies
app/Models/            Company, Service, LocationService, Location
app/Actions/           UpdateCompanyCurrencies (new); UpdateCompany, SetUpSalon, CreateCompany,
                       CreateService, UpdateService, CreateLocationService, UpdateLocationService,
                       CreateDefaultCatalogue
app/Enums/             UserActionEnum (+ CompanyCurrenciesUpdated)
app/Http/Controllers/App/Onboarding/LocationController   currencies validation
app/ViewModels/Onboarding/LocationViewModel               currencies(), currenciesByCountry(),
                                                          currenciesWithPrices(), currencyCounters()
resources/views/app/onboarding/location.blade.php        currency block, removal dialog
docs/specs/decisions.md                                   D2 amended
lang/*.json            through jacques:localize
tests/Unit/            Models, Actions, ViewModels mirroring the files above
tests/Feature/Controllers/App/Onboarding/LocationControllerTest
```

**Structure Decision**: existing folders only. `UpdateCompanyCurrencies` copies
`UpdateCompanyLanguages`: authorize, validate, write, log on the `low` queue.

## Order of work

1. Config, migrations, casts, factories, model methods and their tests.
2. `UpdateCompanyCurrencies`, `UpdateCompany`, then the four price actions and
   `CreateDefaultCatalogue`.
3. `SetUpSalon` and `CreateCompany`.
4. Step 1: controller validation, view model, currency block, removal dialog.
5. D2 in `decisions.md`, `jacques:localize`, Pint, PHPStan, full suite, `migrate:fresh --seed`,
   quickstart.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| No API coverage (VII, spec FR-012) | No public API exists yet | Building it is D27 work, out of scope |
| No price form, list or formatted price (spec FR-008 to FR-011) | No catalogue screen exists yet (003 built actions only) | The rules live in the actions and models; the screens use them when built (R8) |
