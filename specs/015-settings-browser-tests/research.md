# Research: Settings Browser Tests

## R1. Tooling

- **Decision**: reuse `pestphp/pest-plugin-browser ^5.1` with Playwright Chromium, already installed by commit 30f4903, and its `visit()` API as in `tests/Browser/Auth`.
- **Rationale**: FR-006 and the spec assumption; nothing new to install.
- **Alternatives**: Laravel Dusk (a second browser stack, rejected).

## R2. Signing in

- **Decision**: authenticate with `$this->actingAs($user)` before `visit()`, and pilot it on the settings home first. If the session does not reach the browser, fall back to a `signIn()` helper per file that fills the sign in form.
- **Rationale**: the plugin serves the application in the test process, so the session guard is shared; the sign in form is already covered by `SignInTest`.
- **Outcome**: confirmed by the pilot on 2026-10-16; no fallback needed.
- **Alternatives**: signing in through the form in every test (slower, retests the same screen 60 times).

## R3. Permissions

- **Decision**: `$this->grant($user, PermissionEnum::RoleManage)` and `PermissionEnum::LocationManage` from `tests/TestCase.php`; a user with no grant for FR-005.
- **Rationale**: the same helpers the feature tests use; `SettingsViewModel::companyRows()` reads those two permissions.

## R4. Confirmations

- **Decision**: every destructive control opens `x-confirm-dialog` (an Alpine dialog, not `window.confirm`). A test clicks the control, asserts the dialog title, then either presses the confirming action or the cancel button and asserts the record is unchanged (FR-004).
- **Rationale**: a native dialog would need the plugin's dialog handler; the component is plain DOM.

## R5. Two factor authentication

- **Decision**: read the secret from the setup screen's text (`two-factor.blade.php` prints it), compute the code with `PragmaRX\Google2FA\Google2FA::getCurrentOtp()`, type it. For tests starting with two factor on, `User::factory()->twoFactor($secret)` sets it directly.
- **Rationale**: the spec assumption; `pragmarx/google2fa-laravel` is already a dependency.

## R6. Files and pagination

- **Decision**: avatar upload attaches `UploadedFile::fake()->image('avatar.png')->getPathname()` through the plugin's `attach()`, with `Storage::fake()`. "Load more" is tested with 6 activity entries and 11 sent emails, one more than `PER_PAGE` in `LogsViewModel` (5) and `EmailsSentViewModel` (10).
- **Outcome**: the plugin's server (v5.1.2) corrupts binary uploads: `LaravelHttpServer::parseMultipartBody()` runs `mb_ltrim()` on each raw part, so a PNG reaches Laravel as `application/octet-stream`. The upload test is replaced by a refused non image upload, and the removal tests create the avatar with `UpdateStaffAvatar`. The upload path stays covered by the feature tests only.
- **Rationale**: no binary fixture committed. The constants are private, so the counts are literal; changing a page size fails the test, which is wanted.

## R7. Where the tests run

- **Decision**: in `.github/workflows/browser-tests.yml`, unchanged, on SQLite and serially; locally with `vendor/bin/pest --testsuite=Browser`.
- **Rationale**: `tests.yml` explicitly leaves browser tests to that workflow (FR-007). SC-003 is measured on that job: the 6 existing tests take 3.5 s, so about 60 new ones should add under 1 minute.
- **Alternatives**: running them in `tests.yml` on three databases (no assets or browser there, rejected).

## R8. Phone width

- **Decision**: one test per story file runs `visit($url)->on()->mobile()` (confirmed by the pilot) (the plugin's device preset) on its main form, plus the settings home.
- **Rationale**: the spec edge case asks for one per story, not the whole suite twice.
