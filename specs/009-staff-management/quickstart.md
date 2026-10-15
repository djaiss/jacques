# Quickstart: Staff Management

## Prerequisites

Branch `2026-10-15-staff`, dependencies installed, `.env` on SQLite.

## Setup

```bash
php artisan migrate:fresh --seed --quiet
```

The seeder creates the company, its Owner account and the staff record linked to it.

## Validation

| Check | Command | Expected |
| --- | --- | --- |
| Full suite | `php artisan test --compact` | green |
| Staff stories (spec US1 to US4) | `php artisan test --compact --filter=Staff` | green |
| Location assignment and scope | `php artisan test --compact --filter='AssignLocation\|RemoveLocation\|PendingPermissionCheck\|StaffScope'` | green |
| No leftover of the replaced models | `grep -rnE 'Employee\b\|employee_id\|UserLocation\|TeamMemberService' app database routes resources tests` | no output |
| Translations | `php artisan jacques:localize --check` | nothing missing |
| Style | `vendor/bin/pint --test -q` | exit 0 |
| Static analysis | `vendor/bin/phpstan analyse --no-progress -q` | exit 0 |
| Other databases | the `tests.yml` CI job on MySQL and PostgreSQL | green |

## Manual check

Sign in as the seeded Owner. The profile screen and its avatar upload work as before. On a
location's people tab, assigning and removing a staff member works.
