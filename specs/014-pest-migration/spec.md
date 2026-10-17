# Feature Specification: Pest Migration

**Feature Branch**: `2026-10-16-pest-migration`

**Created**: 2026-10-16

**Status**: Draft

**Input**: User description: "je veux passer de phpunit à pest (https://pestphp.com/docs/installation) sur l'ensemble des tests qu'on fait. je veux que tu le fasses avec rigueur. supervise le tout avec opus, mais execute les taches de base avec sonnet 5.5."

## User Scenarios & Testing *(mandatory)*

The users are the contributors to Jacques (people and agents) who write, read and run the test suite.

### User Story 1 - Run the whole suite on Pest (Priority: P1)

A contributor runs the suite locally and in CI, and every existing test runs as a Pest test with the same verdict as before.

**Why this priority**: the migration is worthless if a single behavior stops being checked.

**Independent Test**: run the suite before and after on the same commit of `app/` and compare the list of tests and their results.

**Acceptance Scenarios**:

1. **Given** the 191 test files, 1143 tests and 2954 assertions run on `main`, **When** the migration is done, **Then** every one of them exists as a Pest test and passes.
2. **Given** the CI pipeline on SQLite, MySQL and PostgreSQL, **When** a pull request runs it, **Then** the three jobs run the Pest suite in parallel and pass.
3. **Given** a test that fails on purpose, **When** the suite runs, **Then** it reports the failure and exits with a non zero code.

---

### User Story 2 - Write a new test the Pest way (Priority: P2)

A contributor (or an agent reading the project rules) adds a test and finds rules, a skill and examples that describe Pest only.

**Why this priority**: without updated rules, the next feature reintroduces PHPUnit classes.

**Independent Test**: read `.claude/rules/testing.md`, `.claude/rules/code-style.md`, the tests skill and the constitution, and find no instruction that still requires a PHPUnit class or the `#[Test]` attribute.

**Acceptance Scenarios**:

1. **Given** the updated rules, **When** an agent writes a test for a new action, **Then** it writes a Pest test file with no class.
2. **Given** the constitution says the suite runs on PHPUnit, **When** the migration lands, **Then** the constitution is amended (PATCH or MINOR) and its report says why.

---

### User Story 3 - Review the migration with confidence (Priority: P3)

A reviewer can check that no test was lost, weakened or duplicated, without reading 193 files side by side.

**Why this priority**: the user asked for rigor; the proof has to be inspectable.

**Independent Test**: the pull request carries a before and after inventory (test count and assertion count per file) that the reviewer can reproduce with one command.

**Acceptance Scenarios**:

1. **Given** the inventory, **When** a file has fewer tests or assertions after than before, **Then** the difference is named and justified in the pull request.

### Edge Cases

- A test using a data provider (one file) keeps every case, as a Pest dataset.
- Helpers on the base test case (`grant()`, `giveDefaultRole()`) stay reachable from every test.
- Tests using Mockery or `Queue::fake()` keep their exact expectations.
- Fixture classes under `tests/Fixtures` stay classes; only test files lose their class.
- A test name that collides with another once converted to a sentence is renamed, never dropped.
- Static analysis and Pint still pass over the converted files.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The project MUST install the current major of Pest and its Laravel plugin as development dependencies, with the Laravel and PHPUnit versions they require (research R1).
- **FR-002**: Every test in `tests/Unit` and `tests/Feature` MUST be converted to Pest, with none left as a PHPUnit class.
- **FR-003**: Each converted test MUST check the same behavior as its original, with every `$this->assert*()` call rewritten as an `expect()` chain.
- **FR-003a**: Each rewrite MUST follow one mapping table fixed before conversion starts, and MUST keep the strictness of the original (`assertSame` becomes `toBe`, `assertEquals` becomes `toEqual`, never the reverse).
- **FR-003b**: An assertion with no `expect()` equivalent (database state, model presence) keeps its Laravel form, and the mapping table lists it; assertions on a response object stay method calls on that response.
- **FR-004**: The test and assertion count per file MUST be equal before and after, except where the pull request justifies the difference.
- **FR-005**: Test names MUST keep their meaning and be written as `it('...')` sentences derived from the current `it_...` method names.
- **FR-006**: The base test case and the database refresh MUST be applied per directory through the Pest configuration file, so no test file repeats them.
- **FR-007**: `composer test` and `php artisan test --parallel` MUST run the suite through Pest, in parallel.
- **FR-007a**: `.github/workflows/tests.yml` MUST run the suite with `vendor/bin/pest --parallel` on SQLite, MySQL and PostgreSQL, and its comments naming PHPUnit or Paratest MUST be updated to what is true after the migration.
- **FR-007b**: `.github/workflows/tests.yml` MUST fail when a file under `tests/Unit` or `tests/Feature` declares a class, so the PHPUnit style cannot come back.
- **FR-007c**: The pull request MUST show the three database jobs green on its last commit.
- **FR-008**: `.claude/rules/testing.md`, `.claude/rules/code-style.md`, `.claude/skills/tests/SKILL.md` and the constitution MUST describe Pest and no longer require PHPUnit classes or the `#[Test]` attribute.
- **FR-009**: The migration MUST change no file under `app/`, `routes/`, `database/` or `resources/`.
- **FR-010**: Pint and PHPStan MUST report nothing new after the migration.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of the 1143 tests pass after the migration, minus the scaffolding `ExampleTest` (research R7), on each of the three databases.
- **SC-002**: The per file inventory shows zero unjustified loss of tests or assertions.
- **SC-003**: Zero test files declare a class or import `PHPUnit\Framework\Attributes\Test`.
- **SC-004**: The full suite takes no more than 10% longer than before on the same machine, median of three runs (3.8 s on `main` on 2026-10-16).
- **SC-005**: A new test written by an agent following only the updated rules is a valid Pest test on the first attempt.

## Assumptions

- The suite passes on `main` before the work starts; a test already failing is reported, not fixed silently.
- Pest features the suite does not need today (architecture tests, snapshots, browser testing, mutation testing) are out of scope.
- Fixture classes keep the "one class per file" rule; that rule stops applying to test files, which hold no class.
- Execution model asked by the user: Opus supervises (plan, conversion rules, review of every batch, final verification), and Sonnet 5.5 subagents convert the files in batches by directory. A batch is accepted only when its tests pass and its inventory matches.
- The work ships as one pull request, since a suite half on PHPUnit and half on Pest would need two sets of rules.
