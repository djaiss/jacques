# Implementation Plan: Settings Browser Tests

**Branch**: `2026-10-16-browser-testing` | **Date**: 2026-10-16 | **Spec**: [spec.md](spec.md)

## Summary

About 60 Pest browser tests cover the 11 settings screens, one file per screen, on the plugin and workflow added in commit 30f4903. Tests sign in with `actingAs()` (R2), drive the shared confirm dialog for every destructive control (R4), compute two factor codes from the shown secret (R5). Decisions: [research.md](research.md).

## Technical Context

- **Language/Version**: PHP 8.4, Laravel 13
- **Primary Dependencies**: `pestphp/pest-plugin-browser ^5.1`, Playwright Chromium, `pragmarx/google2fa-laravel`; nothing added
- **Storage**: SQLite, as in the browser workflow; `Storage::fake()` for avatars
- **Testing**: Pest browser tests, testsuite `Browser`
- **Target Platform**: local machines and `.github/workflows/browser-tests.yml`
- **Project Type**: Laravel web application, test suite only
- **Performance Goals**: SC-003, under 3 minutes added; estimate under 1 minute (R7)
- **Constraints**: no production change unless a test exposes a defect (FR-008); no class in a test file
- **Scale/Scope**: 11 screens, 6 destructive areas, 2 permissions

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Not concerned; no application code planned |
| II. Simplicity | Pass. Helpers only where a file repeats a non trivial setup (two factor user, administrator) |
| III. Tested | Pass. The feature is tests; existing feature tests stay |
| IV. Laravel conventions | Pass. Laravel's documented Pest browser plugin |
| V. Localized and safe | Pass. FR-004 proves every destructive action warns first |
| VI. Accessible | Partly served: tests select by visible label and role where possible, and one phone width test per story (R8) |
| VII. One core | Not concerned |
| VIII. Short record | Pass. Spec at its cap, others under |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/015-settings-browser-tests/
├── spec.md  plan.md  research.md  quickstart.md
├── checklists/requirements.md
└── tasks.md            (/speckit-tasks)
```

No `data-model.md` (no data) and no `contracts/` (nothing exposed).

### Source Code

| File under `tests/Browser/Settings/` | Story | Covers |
| --- | --- | --- |
| `SettingsTest.php` | 1 | home, rows, breadcrumb, no "This company" without permission, phone width |
| `Account/ProfileTest.php` | 2 | name and email, validation, avatar upload, removal with confirm and cancel |
| `Account/PreferenceTest.php` | 2 | language, time format, time preview |
| `Account/LogTest.php` | 2 | activity list, load more, empty state |
| `Account/EmailSentTest.php` | 2 | sent emails list, load more, empty state |
| `Account/SecurityTest.php` | 3 | password change and error, API key create, token shown once, revoke with confirm and cancel |
| `Account/TwoFactorTest.php` | 3 | enable with valid and invalid code, recovery codes, disable with confirm and cancel |
| `Administration/RoleTest.php` | 4 | list, create, duplicate, rename, delete with confirm and cancel, refused without permission |
| `Administration/RolePermissionTest.php` | 4 | filter, empty filter, grant and scope, persisted after reload |
| `Administration/RolePeopleTest.php` | 4 | assign, other company absent, remove with confirm and cancel |
| `Administration/LocationTest.php` | 5 | create, edit, validation, archive and restore with confirm and cancel, filters, search, people, refused without permission |

File names follow the controllers, as the testing rules mirror application folders. `tests/Browser/Auth` is unchanged.

**Structure Decision**: `tests/Browser/Settings/{Account,Administration}`, mirroring the settings sections and `app/Http/Controllers/App/Settings`.

## Order of Work

1. Pilot `SettingsTest.php`; it settles R2 and R8. Stop and update research if `actingAs()` fails.
2. Account files, then administration files; each file green alone before the next.
3. A defect found is fixed in its own commit, with a feature or unit test, and named in the pull request (FR-008).
4. Full `Browser` suite three times in a row (SC-004), timed against the 3.5 s baseline (SC-003), then Pint and PHPStan.
5. Run `php artisan jacques:localize`; nothing is expected since no string changes.

## Complexity Tracking

No violation to justify.
