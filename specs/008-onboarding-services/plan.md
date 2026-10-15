# Implementation Plan: Onboarding Step 2, Services

**Branch**: `2026-10-15-onboarding-services-step` | **Date**: 2026-10-15 | **Spec**: [spec.md](spec.md)

## Summary

The step 2 placeholder becomes the catalogue editor of the design. Steps become named, with a
duration and a team member flag, which amends D5. The catalogue actions accept missing texts and
prices. Every edit is a small Alpine Ajax form saved on change, the language is switched on the
client, and removals are undone from a session snapshot. Decisions: [research.md](research.md).
Tables: [data-model.md](data-model.md). Routes: [contracts/routes.md](contracts/routes.md).

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Alpine.js, Alpine Ajax and Tailwind already ship
- **Storage**: SQLite, MySQL, PostgreSQL; the `service_steps` migration is edited (R1)
- **Testing**: PHPUnit, following `.claude/rules/testing.md`
- **Target Platform**: web back office, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: step 2 renders in one request with eager loaded categories, services and steps, about 20 services
- **Constraints**: company isolation on every route, no driver specific SQL, no dashes in prose
- **Scale/Scope**: 1 migration edited, 6 new actions, 7 actions changed, 7 controllers, 1 screen with 6 partials

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Snapshot reading is a helper; every write is an action |
| II. Simplicity | Pass. No new abstraction beyond `DefaultCatalogue` (shared data) and `CatalogueSnapshot` |
| III. Tested | Pass. Every new or changed action, model, view model and controller gets a test |
| IV. Laravel conventions | Pass. Edited migration defines only `up()` |
| V. Localized and safe | Justified: single service and step removals use undo, not a warning (Complexity Tracking) |
| VI. Accessible | Pass, checked on desktop, tablet and phone (quickstart step 7) |
| VII. One core | Justified gap: no API nor webhook exists yet (R10) |
| VIII. Short record | Pass. spec 120 lines, every other artifact under its cap |
| decisions.md D5 | Amended first, in the same pull request (R1) |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/008-onboarding-services/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
├── contracts/routes.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
docs/specs/decisions.md                          D5 amended
database/migrations/2026_10_14_000002_...        service_steps columns (data-model)
database/factories/                              ServiceStepFactory, ServiceFactory
app/Models/                                      Service (busy, free, bookable, missing languages), ServiceStep
app/Helpers/                                     DefaultCatalogue (from CreateDefaultCatalogue), CatalogueSnapshot
app/Actions/                                     new: UpdateServiceDuration, UpdateServicePrices, DestroyCatalogue,
                                                 RestoreDefaultCatalogue, RestoreCatalogueSnapshot, CopyDefaultCatalogue
                                                 changed: CreateService, UpdateService, CreateServiceCategory,
                                                 UpdateServiceCategory, DestroyServiceCategory,
                                                 CreateDefaultCatalogue, CompleteOnboardingStep
app/Enums/UserActionEnum.php                     ServiceDurationUpdated, ServicePricesUpdated, CatalogueDestroyed,
                                                 CatalogueRestored, CatalogueRemovalRestored
app/Http/Controllers/App/Onboarding/             ServiceController (index reads the new view model)
app/Http/Controllers/App/Onboarding/Catalogue/   Category, CategoryPosition, Service, ServiceDuration,
                                                 ServicePrice, Catalogue, Restoration controllers
app/ViewModels/Onboarding/                       ServicesViewModel extends StepViewModel, with
                                                 CatalogueLanguages and ServicePanel blocks
resources/views/app/onboarding/                  services.blade.php, _catalogue-languages, _catalogue-category,
                                                 _service-row, _service-panel, _steps-explainer, _catalogue-footer
routes/web.php                                   routes of contracts/routes.md
lang/*.json                                      through jacques:localize
tests/Unit/{Actions,Models,Helpers,ViewModels}/  mirroring the classes above
tests/Feature/Controllers/App/Onboarding/        one test per controller, including another company's 404
```

**Structure Decision**: the existing Laravel layout; nothing new at the top of `app/`.

## Order of Work

1. D5 amendment, migration, `ServiceStep`, factories, `Service` derived values, and the four catalogue actions relaxed (R1, R2); existing tests migrated.
2. `DefaultCatalogue` extracted, default steps rewritten, `RestoreDefaultCatalogue`, `DestroyCatalogue`, `DestroyServiceCategory(withServices)`.
3. `CatalogueSnapshot`, `RestoreCatalogueSnapshot`, `UpdateServiceDuration`, `UpdateServicePrices`, `CompleteOnboardingStep` offering (R7).
4. View models, routes, controllers, then the Blade screen, list first (US1, US2), panel (US3), languages (US4), empty state and footer (US5).
5. `jacques:localize`, Pint, PHPStan, quickstart.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Principle V: removing one service or step does not warn first | The design trades a dialog per removal for an immediate, complete undo | A dialog on every row removal makes trimming a 17 service example tedious, and undo already prevents the loss |
