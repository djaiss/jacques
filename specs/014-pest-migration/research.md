# Research: Pest Migration

## R1. Pest 5, with PHPUnit 13

- Decision: `pestphp/pest ^5.3` and `pestphp/pest-plugin-laravel ^5.0`, which require `phpunit/phpunit ^13.4` and `brianium/paratest ^7.26`.
- Laravel 13.23 cannot run PHPUnit 13.4 in parallel (`PhpHandler` takes an emitter since 13.35). The user ran `composer update` on 2026-10-16, which moved Laravel to 13.37 and, with it, Guzzle 7 to 8 and brick/math 0.18 to 1.0, two majors. Accepted by the user; the suite is the check.
- Rejected: Pest 4 on PHPUnit 12 (a major behind on the day it lands, and a second migration later).

## R2. Pest drift as a first pass

- Decision (pilot, 2026-10-16): drift is kept. On `tests/Unit/Enums` its output matched the mapping (45 `assertEquals` to 45 `toEqual`, arguments swapped) with the same inventory. Its assertion table is the one of the contract, so the supervisor runs it once over the whole suite, with Pint and a script restoring the blank lines it drops.
- What drift leaves to the batches: `expectException` and `expectExceptionMessage`, `assertNotEquals`, `assertNotSame`, `assertStringContainsString`, `assertStringNotContainsString`, `assertArrayNotHasKey`, the namespace of R3 it removes, `$this->` inside helpers, and the two class constants it cannot convert.
- The supervisor removes the `uses(RefreshDatabase::class)` drift writes in 170 files, since `tests/Pest.php` applies it (R4).
- Drift is removed from `composer.json` before merge.

## R3. One namespace per test file for its helpers

- Decision (user, 2026-10-16): a file with helpers declares `namespace` + its path + its file name, e.g. `Tests\Feature\Controllers\App\Onboarding\Catalogue\ServiceControllerTest`. Helpers become functions with their name and body unchanged.
- Why: 42 files hold private helpers, `owner()` in 20 of them with 15 different bodies, up to 7 in one folder. Two global functions with one name stop the suite at load time.
- A helper calling `$this->grant()` or `$this->giveDefaultRole()` calls `test()->grant()` instead, since a function has no `$this`.
- A file with no helper keeps no namespace.
- Rejected: unique names per file (42 renames for no gain); pooling in `TestCase` (15 `owner()` bodies differ, so a scenario's data would change).

## R4. `Pest.php` carries the base case and the database refresh

- Decision: `tests/Pest.php` applies `Tests\TestCase` to `Unit` and `Feature`, and `RefreshDatabase` to the same folders.
- To verify: the 21 files without `RefreshDatabase` today (69 tests) stay green and SC-004 holds. An in memory database is migrated again for every test that refreshes it, so those 69 tests gain a migration each. If SC-004 fails, `RefreshDatabase` is applied per file with `uses()` instead.

## R5. The inventory is the JUnit report

- Decision: `--log-junit` on `main` (PHPUnit) and on the branch (Pest) gives the test and assertion count per file. `compare-inventory.php` in this folder maps the `class` attribute of each `testcase` to its file (Pest prefixes it with `P\\` and writes no path in `file`) and prints every file whose counts differ.
- `expectException` and `->throws()` each count one assertion, and each `expect()` matcher counts one, so equal counts are the expected result. A data provider case and a dataset case each count as one test.

## R6. Execution model

- Decision: Opus fixes the mapping, runs the pilot, splits the batches, reviews each one (inventory, the greps of quickstart, a reading of the diff) and alone edits `composer.json`, `tests/Pest.php`, CI and the rules. Sonnet 5.5 subagents convert one batch each, in parallel, since batches share no file and every process has its own in memory database.
- A batch returns to its agent with the finding when its inventory differs without reason, a grep fails, or the reading finds a weakened assertion.

## R7. `ExampleTest` is deleted

- Decision: `tests/Unit/ExampleTest.php` is the Laravel scaffolding test (`assertTrue(true)`), the only file extending PHPUnit's own `TestCase`. It checks nothing and is deleted; the pull request names it as the one justified loss of SC-002.
