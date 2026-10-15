# Implementation Plan: Catalogue Languages

**Branch**: `2026-10-14-catalogue-languages` | **Date**: 2026-10-14 | **Spec**: [spec.md](spec.md)

## Summary

A company gets an ordered list of languages, chosen on onboarding step 1 with the block of the
design. Category and service names and descriptions become one text per language, required in
every company language for the name. Decisions are in [research.md](research.md), columns in
[data-model.md](data-model.md), signatures in [contracts/actions.md](contracts/actions.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel

**Primary Dependencies**: none new

**Storage**: SQLite, MySQL and PostgreSQL; three migrations edited in place (pre-production)

**Testing**: PHPUnit, Larastan, Pint

**Target Platform**: web back office

**Project Type**: Laravel web application, Blade and Alpine

**Performance Goals**: reading a text costs no query beyond the item and its company

**Constraints**: a refusal or a record of another company answers as not found

**Scale/Scope**: up to 8 languages per company, a few dozen services

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: one new action, four actions changed, the controller only validates |
| II. Simplicity | Pass: JSON columns and a cast (R1), one helper shared by two models (R6) |
| III. Tested | Pass: every changed model, action, helper, view model and controller has its test |
| IV. Laravel conventions | Pass: migrations edited in place; `up()` only where this feature writes |
| V. Localized, safe | Pass: new copy through `jacques:localize`, the counter through `trans_choice()`, removal confirmed (R8), salon texts never machine translated |
| VI. Accessible | Pass: toggles are buttons with `aria-pressed`, flags hidden, fields carry `lang` and `dir` (R9) |
| VII. One core | Justified: no API exists yet, see Complexity Tracking |
| VIII. Short record | Pass: every artifact within its cap |
| Invariants | D30 to amend before the booking page is built (spec, Assumptions); no other change |

Re-checked after Phase 1: no change.

## Project Structure

### Documentation (this feature)

```text
specs/005-catalogue-languages/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
├── contracts/actions.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
database/migrations/   companies (+ languages), service_categories and services (json texts,
                       unique index removed)
database/factories/    CompanyFactory (+ languages), ServiceCategoryFactory, ServiceFactory (json texts)
config/jacques.php     content_languages, country_languages
app/Helpers/           CatalogueText (new)
app/Models/            Company, ServiceCategory, Service
app/Actions/           UpdateCompanyLanguages (new); SetUpSalon, CreateCompany,
                       CreateServiceCategory, UpdateServiceCategory, CreateService, UpdateService
app/Enums/             UserActionEnum (+ CompanyLanguagesUpdated)
app/Http/Controllers/App/Onboarding/LocationController   languages validation
app/ViewModels/Onboarding/LocationViewModel               languages(), languagesByCountry(),
                                                          languagesWithTexts()
resources/views/app/onboarding/location.blade.php        language block, confirm dialog
lang/*.json            through jacques:localize
tests/Unit/            Helpers, Models, Actions, ViewModels mirroring the files above
tests/Feature/Controllers/App/Onboarding/LocationControllerTest
```

**Structure Decision**: existing folders only. `UpdateCompanyLanguages` mirrors `UpdateCompany`:
authorize, validate, write, log on the `low` queue.

## Order of work

1. Config, migrations, casts, factories, `CatalogueText` and model tests.
2. `UpdateCompanyLanguages`, then the four catalogue actions with per-language validation.
3. `SetUpSalon` and `CreateCompany`.
4. Step 1: controller validation, view model, language block, removal dialog.
5. `jacques:localize`, Pint, PHPStan, full suite, `migrate:fresh --seed`, quickstart.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| No API coverage (VII, spec FR-013, FR-014) | No public API exists yet | Building it is D27 work, out of scope |
| No catalogue form or list (spec FR-009, FR-011, FR-012) | No catalogue screen exists yet (003 built actions only) | The rules live in the actions and `CatalogueText`; the screens use them when built |
