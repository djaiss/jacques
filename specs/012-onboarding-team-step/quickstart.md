# Quickstart: Onboarding Step 3, Your Team

## Prerequisites

Branch `2026-10-15-onboarding-team-step`, dependencies installed, `.env` on SQLite, a queue
worker or `QUEUE_CONNECTION=sync` so the example catalogue exists at sign up, and
`MAIL_MAILER=log` to prove nothing is sent.

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
| Step 3 screens | `php artisan test --compact --filter=Onboarding` | green |
| Staff and invitation actions | `php artisan test --compact --filter='Staff\|Invitation\|CompleteOnboardingStep'` | green |
| Translations | `php artisan jacques:localize --check` | nothing missing |
| Style | `vendor/bin/pint --test -q` | exit 0 |
| Static analysis | `vendor/bin/phpstan analyse --no-progress -q` | exit 0 |
| Other databases | the `tests.yml` CI job on MySQL and PostgreSQL | green |

## Manual check

Sign up a new salon as Michael Scott, finish steps 1 and 2, then on step 3:

1. The only row is Michael, "You", "Account owner", "All N services" (US1, R2).
2. Add Jim Halpert, barber, with the Barbering category; his row shows "3 services" and "Not invited" (US1).
3. Add Pam Beesly, untick bookable; her row shows "Not bookable"; reopen her, tick bookable again, her services are back (US1).
4. Open Dwight's row after adding him with an email, tick the invitation, pick Manager, preview the email, close it with Escape, save; his row shows "Invitation ready" and the footer "1 invitation ready." (US3).
5. Empty Dwight's email and save; the invitation is gone (edge case).
6. Remove Andy Bernard, choose "Keep them", then remove him for good (US2).
7. Choose "Continue to appointments"; step 4 opens and `storage/logs/laravel.log` holds no email (US3, SC-003).
8. Repeat 2 to 4 at phone width and with the keyboard only (FR-017). The footer scrolls with the page.

Screenshots follow the `take-screenshot` skill.
