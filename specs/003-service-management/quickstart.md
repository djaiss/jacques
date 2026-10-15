# Quickstart: Service Management

## Prerequisites

The worktree on branch `2026-10-14-service-management`, dependencies installed.

## Validate

```bash
php artisan migrate:fresh --seed --quiet
```

```bash
php artisan test --compact tests/Unit/Models tests/Unit/Actions
```

```bash
php artisan jacques:localize --check
```

## Scenarios the tests must prove

| Scenario | Spec |
| --- | --- |
| Michael (Owner) creates "Colour" with three steps; it lasts 65 minutes and sits last in "Colours" | US1.1 |
| Deleting "Colours" leaves "Colour" without a category | US1.2 |
| Dwight (Manager) is refused when he edits a service | US1.4, FR-015 |
| Scranton overrides 40.00, Stamford keeps 35.00, then follows the new company price | US2.1, US2.2 |
| Dwight, assigned to Scranton only, is refused on Stamford | US2.5 |
| Jim performs "Haircut" at Scranton; the same at Stamford is refused | US3.1, US3.2 |
| A service of another company, joined to a Scranton record, is not found | FR-013 |
| Removing Jim from Scranton removes what he performed there | Edge cases |
