# Quickstart: Onboarding Step 4, Availability

## Prerequisites

Branch `2026-10-16-onboarding-availability-step`, dependencies installed, `.env` on SQLite, and a
queue worker or `QUEUE_CONNECTION=sync` so the example catalogue exists at sign up.

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
| Step 4 and dashboard screens | `php artisan test --compact --filter='Availability\|StaffHours\|Dashboard'` | green |
| Hour actions | `php artisan test --compact --filter='OpeningHour\|WorkingHours\|ApplyOpeningHoursToTeam\|CompleteOnboardingStep'` | green |
| Translations | `php artisan jacques:localize --check` | nothing missing |
| Style | `vendor/bin/pint --test -q` | exit 0 |
| Static analysis | `vendor/bin/phpstan analyse --no-progress -q` | exit 0 |
| Other databases | the `tests.yml` CI job on MySQL and PostgreSQL | green |

## Manual check

Sign up a new salon as Michael Scott, finish steps 1 to 3 with Jim Halpert (bookable) and Pam
Beesly (not bookable), then on step 4:

1. The tracker reads "Availability", the week shows the default hours and "6 days, 57 h a week" (US1, FR-005).
2. Untick Monday: it reads "Closed", every row's summary drops to "5 days" at once (US1, US2).
3. Open Saturday, choose "Close for lunch, 12:00 to 13:00": two bars appear (US1).
4. Set a range of Thursday to end before it starts: the error shows in text, the footer turns red and "Finish setup" is disabled; fix it (US1).
5. Michael and Jim are listed, Pam is not (edge case).
6. Untick "Use salon opening hours", customize Jim with Tuesday to Friday and a lunch break, add a Sunday range and read the warning, save; Jim reads "Custom hours", the footer "2 team members, 1 with custom hours." (US3).
7. Reload: the opening hours are back to the default, Jim keeps his custom hours and the box stays unticked (US4, edge case).
8. Choose "Finish setup": the dashboard opens; Michael works the opening hours, Jim his own (US1, US2, SC-005).
9. Open `/onboarding`: the home screen shows (spec 004).

Repeat 8 on the phone and tablet widths, with the keyboard only: the panel traps focus and returns it to the row's button (FR-014).
