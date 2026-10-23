# Implementation Plan: Services screens

**Branch**: `2026-10-23-services-screens` | **Date**: 2026-10-23 | **Spec**: [spec.md](spec.md)

## Summary

A Services area outside settings: list (all salons or one), service page, add and edit pages for a
service, add and edit pages for a category, archive and restore. Every write reuses the catalogue
actions; one new action sets where a service is offered and by whom. The service page statistics
come from one sample generator, flagged on screen. No schema change. Decisions:
[research.md](research.md). Routes: [contracts/routes.md](contracts/routes.md). What the form maps
to: [data-model.md](data-model.md).

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Blade, Tailwind, Alpine.js, Alpine Ajax
- **Storage**: SQLite, MySQL, PostgreSQL; existing tables only
- **Testing**: Pest unit tests (actions, view models, sample generator), feature tests per controller, one browser test per user story
- **Target Platform**: web back office, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: the list in a fixed number of queries whatever the catalogue size (categories, services, steps, offers and their staff eager loaded); about 50 services, 5 salons, 30 people
- **Constraints**: company isolation on every route, no driver specific SQL, translations in every locale, no dashes in prose
- **Scale/Scope**: 1 new action, 7 controllers, 6 view models plus the sample generator, 6 screens, top menu tile

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Existing actions for service, category, archive; new `SyncServiceOffers` for salons and people (R2) |
| II. Simplicity | Pass. One form partial shared by add and edit; no form request; sample figures in one class (R6) |
| III. Tested | Pass. New action, every view model and controller tested, another company's records included |
| IV. Laravel conventions | Pass. Resource style controllers, implicit binding, inline validation |
| V. Localized and safe | Pass. Archive and category removal confirmed; counts through `trans_choice()` |
| VI. Accessible | Pass, quickstart scenario 9; warnings carry words, not colour alone |
| VII. One core | Justified gap: the API was dropped by the user for this feature; no API exists yet |
| VIII. Short record | Pass. Every artifact under its cap |
| D5, D8 | Pass. Steps edited as in onboarding; per salon prices kept untouched when a salon is unticked (R3) |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/019-services-screens/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
├── contracts/routes.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
app/Actions/SyncServiceOffers.php                     salons and people of one service (R2, R3)
app/Http/Controllers/App/Services/                    ServiceController, ServiceLocationController,
                                                      CategoryServiceController, ServiceArchiveController,
                                                      ServiceCategoryController
app/ViewModels/Services/                              ServicesViewModel, ServiceViewModel,
                                                      ServiceFormViewModel, ServiceCategoryFormViewModel,
                                                      SampleServiceStatistics (R6)
app/View/Components/TopBarLayout.php                  servicesUrl, like teamUrl
resources/views/components/top-bar.blade.php          Services tile links
resources/views/app/services/                         index, show, new, edit, _form, _row,
                                                      categories/new, categories/edit
routes/web.php                                        routes of contracts/routes.md
lang/*.json                                           through jacques:localize
tests/Unit/{Actions,ViewModels/Services}/, tests/Feature/Controllers/App/Services/, tests/Browser/Services/
```

**Structure Decision**: a new `Services` domain beside `Settings` and `Onboarding`, as the menu
treats services as a part of the salon, not a setting. Onboarding is untouched.

## Order of Work

1. `SyncServiceOffers` and its tests (US2 to US4 foundation).
2. List, salon filter, search, menu tile (US1).
3. Add service and add category pages (US2).
4. Service page with the sample statistics (US3).
5. Edit page, archive and restore (US4).
6. Edit and remove a category (US5).
7. `jacques:localize`, Pint, PHPStan, suite, quickstart at three widths in both themes.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| VII, no API for these operations | The user removed the API from the request | Building it would contradict the request |
