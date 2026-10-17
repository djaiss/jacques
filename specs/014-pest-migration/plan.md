# Implementation Plan: Pest Migration

**Branch**: `2026-10-16-pest-migration` | **Date**: 2026-10-16 | **Spec**: [spec.md](spec.md)

## Summary

The 191 test files move from PHPUnit classes to Pest 5 on PHPUnit 13, with every `$this->assert*()` rewritten as `expect()` through one table, [contracts/assertion-mapping.md](contracts/assertion-mapping.md). Pest drift converts the structure if a pilot shows it helps (R2). Opus supervises; Sonnet 5.5 subagents convert batches in parallel (R6). The JUnit report before and after proves no test or assertion was lost (R5). Decisions: [research.md](research.md).

## Technical Context

- **Language/Version**: PHP 8.4, Laravel 13.23
- **Primary Dependencies**: new `pestphp/pest ^5.3`, `pestphp/pest-plugin-laravel ^5.0`; raised `phpunit/phpunit ^13.4`, `brianium/paratest ^7.26`; `pestphp/pest-plugin-drift ^5.0` during the work only
- **Storage**: unchanged; in memory SQLite locally, SQLite, MySQL and PostgreSQL in CI
- **Testing**: Pest, the subject of the feature
- **Target Platform**: local machines and GitHub Actions
- **Project Type**: Laravel web application, test suite only
- **Performance Goals**: SC-004, 3.8 s for 1143 tests on `main`, at most 10% more
- **Constraints**: no file under `app/`, `routes/`, `database/`, `resources/` changes (FR-009); `composer -w`, never `-W` (R1)
- **Scale/Scope**: 191 files, 1143 tests, 2954 assertions, 42 files with helpers, 1 data provider

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Not concerned; no application code changes |
| II. Simplicity | Pass. Helpers keep their names and bodies (R3); no new test abstraction |
| III. Tested | Pass. Every test survives, proven per file (R5); the one deletion is justified (R7) |
| IV. Laravel conventions | Pass. Pest is the runner Laravel offers at install; its Laravel plugin is used as documented |
| V, VI | Not concerned; no string and no screen changes |
| VII. One core | Not concerned |
| VIII. Short record | Pass. Every artifact under its cap |
| Technology constraints | Amendment owed: "The suite runs on PHPUnit" becomes "on Pest". MINOR, 1.3.0 to 1.4.0, since `testing.md` changes materially with it |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/014-pest-migration/
├── spec.md  plan.md  research.md  quickstart.md  compare-inventory.php
├── contracts/assertion-mapping.md
└── tasks.md            (/speckit-tasks)
```

No `data-model.md`: the feature has no data.

### Source Code

```text
composer.json, composer.lock      Pest 5, PHPUnit 13, Paratest; `test` script unchanged (artisan test calls Pest)
tests/Pest.php                    new: TestCase and RefreshDatabase on Unit and Feature (R4)
tests/TestCase.php                unchanged
tests/Unit/**, tests/Feature/**   190 files converted, ExampleTest deleted
tests/Fixtures/**                 unchanged
.github/workflows/tests.yml       `vendor/bin/pest --parallel`, class guard step (FR-007a, FR-007b)
.claude/rules/testing.md          Pest conventions, the mapping, the namespace rule of R3
.claude/rules/code-style.md       "Test Classes" becomes "Test Files"; one class per file applies to fixtures
.claude/skills/tests/SKILL.md     `#[Test]` rule replaced by `it()`
.specify/memory/constitution.md   amendment 1.4.0
```

**Structure Decision**: the existing `tests/` layout, unchanged.

## Batches

| Batch | Files | Who |
| --- | --- | --- |
| 0. Pilot | `Unit/Enums` (6) | Opus, decides R2 |
| 1 | `Unit/Actions` A to `CreateLocation*` (21) | Sonnet |
| 2 | `Unit/Actions` `CreateMagicLink` to `DestroyService*` (21) | Sonnet |
| 3 | `Unit/Actions` `DestroyStaffInvitation` to the file before `SyncStaffWorkingHours` (21) | Sonnet |
| 4 | `Unit/Actions` `SyncStaffWorkingHours` to the end (22) | Sonnet |
| 5 | `Unit/Models` (19) | Sonnet |
| 6 | `Unit/Helpers`, `Jobs`, `Mail`, `Console`, `Permissions` (18) | Sonnet |
| 7 | `Unit/ViewModels` (17) | Sonnet |
| 8 | `Feature/Controllers/App/Onboarding/**` (14) | Sonnet |
| 9 | `Feature` root, `Controllers/App` root, `Auth`, `Settings/**` (31) | Sonnet |

Exact file lists go into `tasks.md`. Batches 1 to 9 start together after the pilot.

## Order of Work

1. Baseline: `before.xml` from `main` (R5), three timed runs.
2. Dependencies (R1) and `tests/Pest.php` (R4); the PHPUnit suite still passes through Pest.
3. Pilot, then the decision on drift written into `research.md`.
4. Batches 1 to 9, each reviewed: tests green, its inventory equal, quickstart greps clean, diff read.
5. ExampleTest deleted, drift removed, CI, rules, skill, constitution.
6. Full inventory, three timed runs, Pint, PHPStan, CI on the three databases.

## Complexity Tracking

No violation to justify.
