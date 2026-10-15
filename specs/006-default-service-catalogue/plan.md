# Implementation Plan: Default Service Catalogue

**Branch**: `2026-10-14-default-service-catalogue` | **Date**: 2026-10-14 | **Spec**: [spec.md](spec.md)

## Summary

Signing up dispatches the queued job `CreateDefaultServices` with the company id. It runs the
action `CreateDefaultCatalogue`, which copies the 6 categories, 17 services and 20 steps once,
names in the four interface locales, and offers them at the headquarters when it exists. Step 1
offers them when it creates the headquarters, whichever comes second. Decisions are in
[research.md](research.md), columns in [data-model.md](data-model.md), signatures in
[contracts/actions.md](contracts/actions.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel

**Primary Dependencies**: none new

**Storage**: SQLite, MySQL and PostgreSQL; one migration edited in place (pre-production)

**Testing**: PHPUnit, Larastan, Pint

**Target Platform**: web back office, database queue

**Project Type**: Laravel web application

**Performance Goals**: the copy runs in one transaction of about 60 inserts, well under a second

**Constraints**: idempotent under retries and double dispatch; nothing crosses companies

**Scale/Scope**: one run per new company

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: two new actions; the job only loads the company and calls one (R1) |
| II. Simplicity | Pass: the catalogue is a literal array in the action, one date column (R3) |
| III. Tested | Pass: job, both actions, `CreateCompany`, `SetUpSalon` and `Company` tested |
| IV. Laravel conventions | Pass: `afterCommit()`, `$tries`, `failed_jobs`; migration edited in place as in 005 |
| V. Localized, safe | Pass: names through `__()` and `jacques:localize` (R5); no salon text translated; no destructive UI |
| VI. Accessible | Not affected: no screen changes |
| VII. One core | Justified: see Complexity Tracking |
| VIII. Short record | Pass: every artifact within its cap |
| Invariants | No change; prices are catalogue values, Jacques charges nothing (D22) |

Re-checked after Phase 1: no change.

## Project Structure

### Documentation (this feature)

```text
specs/006-default-service-catalogue/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
├── contracts/actions.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
database/migrations/   companies (+ default_catalogue_created_at)
app/Models/            Company (cast, property)
app/Jobs/              CreateDefaultServices (new)
app/Actions/           CreateDefaultCatalogue, OfferServicesAtLocation (new); CreateCompany, SetUpSalon
lang/*.json            through jacques:localize
tests/Unit/Jobs/       CreateDefaultServicesTest
tests/Unit/Actions/    CreateDefaultCatalogueTest, OfferServicesAtLocationTest,
                       CreateCompanyTest, SetUpSalonTest
tests/Unit/Models/     CompanyTest
```

**Structure Decision**: existing folders only. `CreateDefaultCatalogue` writes the models
directly, not through `CreateService` (R2).

## Order of work

1. Migration column, `Company` cast, factory default left null.
2. `OfferServicesAtLocation` and its test.
3. `CreateDefaultCatalogue` and its test: counts, order, prices, step totals, four languages, once only, deletion kept, headquarters offered, other company untouched.
4. `CreateDefaultServices` and its test; dispatch from `CreateCompany` with `Queue::assertPushedOn('high', ...)`.
5. `SetUpSalon` offers on creation only; tests for both orders of job and step 1.
6. `jacques:localize`, translate the 23 names in `fr_FR`, `de_DE`, `es_ES`; Pint, PHPStan, full suite, quickstart.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Catalogue actions bypassed (I, R2) | Signup has no author with a permission, and the company speaks `en` only | Loosening `CreateService` would weaken the back office and API rules |
| No API or webhook (VII) | Nothing a person does; no public API exists yet | Out of scope (D27) |
