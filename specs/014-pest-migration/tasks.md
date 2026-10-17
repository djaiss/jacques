# Tasks: Pest Migration

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/assertion-mapping.md](contracts/assertion-mapping.md), [quickstart.md](quickstart.md)

**Tests**: the feature is the test suite; each batch is validated by its own run, its inventory and the greps of quickstart section 3.

**Who**: tasks marked Opus are done by the supervisor in this session; batch tasks are given to one Sonnet 5.5 subagent each (`model: "sonnet"`), then reviewed by Opus before they are checked.

## Phase 1: Setup

- [X] T001 Opus: write the PHPUnit baseline to `before.xml` at the repository root with `php artisan test --parallel --log-junit before.xml` on this branch before any test changes (identical to `main` for `tests/`), and record the median of three timed runs in research.md R5 (quickstart section 1)
- [X] T002 Opus: run `composer require --dev -w phpunit/phpunit:^13.4 brianium/paratest:^7.26 pestphp/pest:^5.3 pestphp/pest-plugin-laravel:^5.0 pestphp/pest-plugin-drift:^5.0`, then check with `git diff composer.lock` that no package outside PHPUnit, Paratest, `sebastian/*`, `phpunit/*`, Pest and the patch or minor releases listed in research.md R1 moved (FR-001)
- [X] T003 Opus: create tests/Pest.php with `pest()->extend(Tests\TestCase::class)->use(Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Unit', 'Feature')` (R4), and confirm `vendor/bin/pest --parallel` still runs the 1143 PHPUnit tests green

## Phase 2: Foundational (pilot and batch brief)

- [X] T004 Opus: convert tests/Unit/Enums (6 files) with `vendor/bin/pest --drift tests/Unit/Enums` (if drift takes no path, on a scratch copy of the suite, keeping only Enums), correct the output to contracts/assertion-mapping.md, and compare it with a conversion by hand of one of its files
- [X] T005 Opus: confirm that Pest's JUnit report carries the `file` attribute read by specs/014-pest-migration/compare-inventory.php, and that tests/Unit/Enums shows no difference against `before.xml`
- [X] T006 Opus: write the drift decision (kept or dropped for the batches) into research.md R2
- [X] T007 Opus: write the batch brief once, given verbatim to every subagent: the files of its batch only, drift or not per T006, contracts/assertion-mapping.md, research.md R3 (namespace per file with helpers, `test()->` in helpers) and R4 (no `uses()` in files), `it('...')` names from the `it_...` method names with underscores turned into spaces, `declare(strict_types=1);` kept, no comment added, no file outside the batch touched, `vendor/bin/pest <batch paths>` green before returning, and a report of every assertion form not in the mapping

## Phase 3: User Story 1, the whole suite runs on Pest (P1) MVP

Independent test: `vendor/bin/pest --parallel` passes, and compare-inventory.php against `before.xml` prints only the ExampleTest line.

- [X] T008 [P] [US1] Sonnet batch 1: tests/Unit/Actions ActivateLocationStaff to CreateLocation (21 files: ActivateLocationStaff, ActivateService, ActivateStaff, ApplyOpeningHoursToTeam, ArchiveLocation, AssignLocation, AssignRole, AssignStaffLocationService, AttemptSignIn, CompleteOnboardingStep, ConfirmEmailAddress, ConfirmTwoFactorAuthentication, ConsumeMagicLink, CopyDefaultCatalogue, CreateApiKey, CreateCompany, CreateDefaultCatalogue, CreateDefaultRoles with its data provider as a dataset, CreateEmailSent, CreateLocationService, CreateLocation)
- [X] T009 [P] [US1] Sonnet batch 2: tests/Unit/Actions CreateMagicLink to DestroyStaffAvatar (21 files: CreateMagicLink, CreateRole, CreateServiceCategory, CreateService, CreateStaffAbsence, CreateStaff, CreateStaffWorkingHour, CreateUser, DeactivateLocationStaff, DeactivateService, DeactivateStaff, DestroyApiKey, DestroyCatalogue, DestroyCompany, DestroyLocationService, DestroyLocation, DestroyRole, DestroyServiceCategory, DestroyService, DestroyStaffAbsence, DestroyStaffAvatar)
- [X] T010 [P] [US1] Sonnet batch 3: tests/Unit/Actions DestroyStaffInvitation to SyncStaffLocationServices (21 files: DestroyStaffInvitation, DestroyStaff, DestroyStaffWorkingHour, DestroyUser, DisableTwoFactorAuthentication, DismissWelcomeScreen, EnableTwoFactorAuthentication, OfferServicesAtLocation, PrepareStaffInvitation, RegenerateTwoFactorRecoveryCodes, RemoveLocation, RemoveRole, RemoveStaffLocationService, ResizeImage, RestoreCatalogueSnapshot, RestoreDefaultCatalogue, RestoreLocation, SendEmailVerification, SetUpSalon, SyncLocationOpeningHours, SyncStaffLocationServices)
- [X] T011 [P] [US1] Sonnet batch 4: tests/Unit/Actions SyncStaffWorkingHours to VerifyTwoFactorCode (22 files: SyncStaffWorkingHours, UpdateCompanyCurrencies, UpdateCompanyLanguages, UpdateCompany, UpdateLocationService, UpdateLocation, UpdatePreferences, UpdateRole, UpdateServiceCategoryPosition, UpdateServiceCategory, UpdateServiceDuration, UpdateServicePosition, UpdateServicePrices, UpdateService, UpdateStaffAbsence, UpdateStaffAvatar, UpdateStaffInformation, UpdateStaffPosition, UpdateStaffWorkingHour, UpdateUserInformation, UpdateUserPassword, VerifyTwoFactorCode)
- [X] T012 [P] [US1] Sonnet batch 5: tests/Unit/Models (19 files)
- [X] T013 [P] [US1] Sonnet batch 6: tests/Unit/Helpers, tests/Unit/Jobs, tests/Unit/Mail, tests/Unit/Console, tests/Unit/Permissions (18 files)
- [X] T014 [P] [US1] Sonnet batch 7: tests/Unit/ViewModels and its subfolders (17 files)
- [X] T015 [P] [US1] Sonnet batch 8: tests/Feature/Controllers/App/Onboarding and its subfolders (14 files)
- [X] T016 [P] [US1] Sonnet batch 9: tests/Feature/SessionCookieTest.php, tests/Feature/FaviconTest.php, tests/Feature/Controllers/App/DashboardControllerTest.php, tests/Feature/Controllers/App/WelcomeControllerTest.php, tests/Feature/Controllers/App/Auth, tests/Feature/Controllers/App/Settings (31 files)
- [X] T017 [US1] Opus: review each batch as it returns (its run green, its files without difference in compare-inventory.php, quickstart section 3 greps empty on its paths, its diff read for a swapped `expect()` subject or a `toBe` turned `toEqual`), and send a failed batch back to its subagent with the finding
- [X] T018 [US1] Opus: delete tests/Unit/ExampleTest.php (R7)
- [X] T019 [US1] Opus: replace `php artisan test --parallel` with `vendor/bin/pest --parallel` in the `Run tests` step of .github/workflows/tests.yml and check its Paratest comment still holds (FR-007a)
- [X] T020 [US1] Opus: add a step before `Run tests` in .github/workflows/tests.yml that fails when `grep -rlE '^(final |abstract )?class ' tests/Unit tests/Feature` prints anything (FR-007b)
- [X] T021 [US1] Opus: run the full suite, compare-inventory.php against `before.xml` (only the ExampleTest line), and three timed runs against SC-004 (quickstart section 4)

## Phase 4: User Story 2, a new test is written the Pest way (P2)

Independent test: the four files below no longer require a PHPUnit class or `#[Test]`.

- [X] T022 [P] [US2] Opus: rewrite .claude/rules/testing.md for Pest: `it('...')` instead of `#[Test]` and snake_case names, `expect()` per contracts/assertion-mapping.md, Laravel assertions kept, `tests/Pest.php` applying TestCase and RefreshDatabase, the namespace per file with helpers and `test()->` inside them (R3), datasets instead of data providers
- [X] T023 [P] [US2] Opus: rename "Test Classes" to "Test Files" in .claude/rules/code-style.md, state that a test file holds no class, and keep the one class per file rule for tests/Fixtures
- [X] T024 [P] [US2] Opus: replace the `#[Test]` rule of .claude/skills/tests/SKILL.md with the Pest form and an example taken from a converted Action test
- [X] T025 [P] [US2] Opus: amend .specify/memory/constitution.md to 1.4.0 ("The suite runs on Pest", principle III pointing at the updated testing.md) with its Sync Impact Report entry

## Phase 5: User Story 3, the migration can be reviewed (P3)

Independent test: a reviewer reproduces the inventory with the two commands of quickstart.md and reads the same output as the pull request.

- [X] T026 [US3] Opus: put the compare-inventory.php output, the three durations before and after, and every justified difference (ExampleTest, any count change accepted in T017) into the pull request description

## Phase 6: Polish

- [X] T027 Opus: remove `pestphp/pest-plugin-drift` with `composer remove --dev -w` and run the suite again (R2)
- [X] T028 Opus: run `vendor/bin/pint --test` and `vendor/bin/phpstan analyse`, and `git diff --stat main -- app routes database resources`, which must be empty (FR-009, FR-010)
- [X] T029 Opus: deliberately break one `toBe` in a converted test, confirm a non zero exit code, revert (spec US1 scenario 3)
- [X] T030 Opus: delete `before.xml` and `after.xml`, commit, open the pull request, and wait for SQLite, MySQL and PostgreSQL to pass (FR-007c)

## Dependencies

- T001 before T002 (the baseline is taken on PHPUnit 12). T002, T003 before T004.
- T004 to T007 before T008 to T016. T008 to T016 run together; T017 follows each one.
- T018 to T021 after T017 for every batch. Phase 4 can run alongside Phase 3 once T007 is done. T026 after T021. Phase 6 last.

## Implementation Strategy

US1 is the MVP and the bulk of the work: once T021 passes, the suite is on Pest and proven complete. US2 keeps the next feature from bringing PHPUnit back, and must ship in the same pull request (spec assumptions). US3 is the proof written down.
