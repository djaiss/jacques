# Implementation Plan: Screenshot Sign In Link

**Branch**: `2026-10-15-screenshot-sign-in-link` | **Date**: 2026-10-15 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/010-screenshot-sign-in-link/spec.md`

## Summary

A `jacques:screenshot-link` command prints a temporary signed URL. Opening it on a development
instance signs the named account in and redirects to a local path, so Iris can capture screens
behind the sign in. A shared helper decides availability and development hosts. The take-screenshot
skill is rewritten for this project. Decisions are in [research.md](research.md), the interface in
[contracts/screenshot-link.md](contracts/screenshot-link.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel

**Primary Dependencies**: Laravel signed URLs (`URL::temporarySignedRoute`, `signed` middleware)

**Storage**: none (no table, no migration)

**Testing**: PHPUnit, `tests/Unit/Console/Commands`, `tests/Unit/Helpers`, `tests/Feature/Controllers/App/Auth`

**Target Platform**: development instances only (Herd on `.test`, `php artisan serve`)

**Project Type**: web application (Laravel monolith)

**Performance Goals**: none beyond a normal request

**Constraints**: refused in production and on any host outside R5; link lifetime 5 minutes by default

**Scale/Scope**: 1 command, 1 controller, 1 helper, 1 route, 2 config keys, 1 skill rewrite

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. No record changes, so no action (R2). Controller stays thin, like `MagicLinkController::show`. |
| II. Simplicity | Pass. One helper, justified by two callers (R1). |
| III. Tested before merged | Pass. Command, helper and controller each get a test (FR-013). |
| IV. Laravel conventions | Pass. Built on signed routes; `env()` only in `config/jacques.php`. |
| V. Localized and safe UX | Pass. No screen, console copy untranslated as in `LocalizeCommand` (R9). |
| VI. Accessible | Not applicable, no screen. |
| VII. One core for every surface | Not applicable, a development tool, not a capability. |
| VIII. Short record | Pass. `data-model.md` omitted: nothing is stored. |
| Product invariants | Pass. The link exists only on development instances. |
| Folders | Pass. `app/Helpers`, `app/Console/Commands`, `app/Http/Controllers/App/Auth`, `routes/auth.php`. |

Post-design re-check: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/010-screenshot-sign-in-link/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/screenshot-link.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Helpers/ScreenshotLink.php                              # isAvailable(), isDevelopmentHost()
app/Console/Commands/ScreenshotLinkCommand.php              # jacques:screenshot-link
app/Http/Controllers/App/Auth/ScreenshotLinkController.php  # show()
routes/auth.php                                             # GET screenshot-link, auth.screenshotLink.show
config/jacques.php                                          # screenshot_link_enabled, screenshot_link_duration_minutes
.env.example                                                # the two variables, setting false
.claude/skills/take-screenshot/SKILL.md                     # jacques.test, seeded address, real screen
tests/Unit/Helpers/ScreenshotLinkTest.php
tests/Unit/Console/Commands/ScreenshotLinkCommandTest.php
tests/Feature/Controllers/App/Auth/ScreenshotLinkControllerTest.php
```

**Structure Decision**: existing Laravel folders only; no new top level folder.

## Complexity Tracking

None.
