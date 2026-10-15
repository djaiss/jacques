# Quickstart: Onboarding Step 2, Services

## Prerequisites

Branch `2026-10-15-onboarding-services-step`, dependencies installed, `.env` on SQLite, a queue
worker or `QUEUE_CONNECTION=sync` so the example catalogue exists at sign up.

## Setup

```bash
php artisan migrate:fresh --seed --quiet
```

```bash
npm run build --silent
```

## Validation

| Check | Command | Expected |
| --- | --- | --- |
| Full suite | `php artisan test --compact` | green |
| Step 2 screens | `php artisan test --compact --filter=Onboarding` | green |
| Catalogue actions | `php artisan test --compact --filter='Service\|Catalogue'` | green |
| No leftover of the old step columns | `grep -rnE 'active_duration\|processing_duration' app database resources tests` | no output |
| Translations | `php artisan jacques:localize --check` | nothing missing |
| Style | `vendor/bin/pint --test -q` | exit 0 |
| Static analysis | `vendor/bin/phpstan analyse --no-progress -q` | exit 0 |
| Other databases | the `tests.yml` CI job on MySQL and PostgreSQL | green |

## Manual check

Sign up a new salon, finish step 1 with English and French and two currencies, then on step 2:

1. Change a price of "Men's Haircut" in the row; the footer says "Saved"; reload, the value stays (US1).
2. Remove "Root Touch-up", click "Undo"; it is back in second place (US1).
3. Move "Barbering" up, remove "Hair Treatments" after the warning (US2).
4. Open "Balayage", break it into three steps, 45 busy, 30 free, 30 busy; the list shows 105 min and "3 steps" (US3).
5. Pick French, check the counts on the language cards, write a missing French name (US4).
6. Empty the catalogue, restore the example, choose "I'll set this up later"; step 3 opens (US5).
7. Repeat 1 to 4 at phone width and with the keyboard only (FR-018).

Screenshots follow the `take-screenshot` skill.
