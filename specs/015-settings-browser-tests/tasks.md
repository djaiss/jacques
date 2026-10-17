# Tasks: Settings Browser Tests

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

Every task writes Pest browser tests following `.claude/rules/testing.md` and the style of tests/Browser/Auth/SignInTest.php: `declare(strict_types=1);`, `it('...')` sentences, The Office (US) data, `->assertNoJavaScriptErrors()` on the last step of each happy path, no comment, no class. Every confirmation test presses the control, asserts the dialog title, then covers both the cancel button ("Cancel") with the record unchanged and the confirming button (FR-004, R4). Run each file with `vendor/bin/pest <file>` before marking it done.

## Phase 1: Setup

- [X] T001 Build assets and install Chromium with `bun run build` and `bunx playwright install chromium`, then confirm `vendor/bin/pest --testsuite=Browser` passes its 6 existing tests (quickstart)

## Phase 2: Foundational (pilot)

- [X] T002 Write one test in tests/Browser/Settings/SettingsTest.php that calls `$this->actingAs($user)` on a user from `User::factory()->create()` then `visit(route('settings.index'))` and asserts "Settings"; if the session does not reach the browser, switch to signing in through the form (R2) and record the outcome in research.md R2
- [X] T003 In the same file, check `$page->on()->mobile()` works on the settings home, and record the outcome in research.md R8

## Phase 3: User Story 1, settings home and navigation (P1) MVP

Independent test: `vendor/bin/pest tests/Browser/Settings/SettingsTest.php` passes.

- [X] T004 [US1] In tests/Browser/Settings/SettingsTest.php: a user granted `PermissionEnum::LocationManage` and `PermissionEnum::RoleManage` with `$this->grant()` sees "You" and "This company", the name and role card, and each row (Profile, Preferences, Security and access, Logs, Locations, Roles and permissions) opens its screen and the "Settings" breadcrumb leads back
- [X] T005 [US1] In tests/Browser/Settings/SettingsTest.php: a user with no grant sees no "This company" section, and visiting `route('settings.roles.index')` and `route('settings.locations.index')` shows no administration screen (FR-005)

## Phase 4: User Story 2, own account: profile, preferences, logs (P1)

Independent test: `vendor/bin/pest tests/Browser/Settings/Account` passes for the four files below.

- [X] T006 [P] [US2] tests/Browser/Settings/Account/ProfileTest.php: saving a new name with "Save" shows it on the screen; an invalid email shows an error and keeps the typed values; one test at phone width (R8)
- [X] T007 [US2] (partial: a real image upload cannot pass the browser plugin, research.md R6) tests/Browser/Settings/Account/ProfileTest.php: with `Storage::fake()`, "Upload" of `UploadedFile::fake()->image('avatar.png')->getPathname()` through `attach()` replaces the initials (R6); "Remove it" opens "Remove your avatar?", cancel keeps the avatar, confirming removes it
- [X] T008 [P] [US2] tests/Browser/Settings/Account/PreferenceTest.php: choosing another value in the "Interface language" picker redraws the screen in that language; choosing another "Time format" changes the "It is :time where you are" preview and persists after a reload
- [X] T009 [P] [US2] tests/Browser/Settings/Account/LogTest.php: "No activity yet" with no record; with 6 activity entries, 5 show and "Load more" shows the sixth without a script error (R6)
- [X] T010 [P] [US2] tests/Browser/Settings/Account/EmailSentTest.php: "No emails yet" with no record; with 11 sent emails, 10 show and "Load more" shows the eleventh; the logs screen link "Every email" opens this screen

## Phase 5: User Story 3, own account: security (P1)

Independent test: `vendor/bin/pest tests/Browser/Settings/Account/SecurityTest.php tests/Browser/Settings/Account/TwoFactorTest.php` passes.

- [X] T011 [P] [US3] tests/Browser/Settings/Account/SecurityTest.php: the password form "Save" with the right current password confirms the change and the new password works (`Hash::check`); a wrong current password shows an error; one test at phone width (R8)
- [X] T012 [US3] tests/Browser/Settings/Account/SecurityTest.php: "Create key" with a label shows the token once and the key in the list, and the token is gone after a reload; "Revoke" opens the dialog, cancel keeps the key, "Revoke it" removes it; "No API keys yet" with none
- [X] T013 [P] [US3] tests/Browser/Settings/Account/TwoFactorTest.php: "Turn it on" opens the setup screen; a code from `(new Google2FA)->getCurrentOtp($secret)`, the secret read from the screen, turns it on and shows recovery codes; an invalid code is refused and leaves it off (R5)
- [X] T014 [US3] tests/Browser/Settings/Account/TwoFactorTest.php: starting from `User::factory()->twoFactor($secret)`, "Get new codes" asks "Get new recovery codes?" and shows codes different from the old ones; "Turn it off" asks "Turn two factor off?", cancel keeps it on, confirming turns it off

## Phase 6: User Story 4, administration: roles and permissions (P2)

Independent test: `vendor/bin/pest tests/Browser/Settings/Administration/Role*` passes.

- [X] T015 [P] [US4] tests/Browser/Settings/Administration/RoleTest.php: with `PermissionEnum::RoleManage`, "New role" then "Create the role" adds and opens it; "Duplicate" adds a copy; renaming with "Save changes" updates the list; one test at phone width (R8)
- [X] T016 [US4] tests/Browser/Settings/Administration/RoleTest.php: "Delete this role" opens the dialog, cancel keeps the role, confirming removes it from the list; a user without the permission is refused (FR-005)
- [X] T017 [P] [US4] tests/Browser/Settings/Administration/RolePermissionTest.php: typing in "Filter permissions" narrows the list and a nonsense filter shows "No permission matches that."; granting a permission, choosing its scope and saving persists after a reload
- [X] T018 [P] [US4] tests/Browser/Settings/Administration/RolePeopleTest.php: assigning a colleague shows them in the people tab; a user of another company is not offered; "Take it back" asks first, cancel keeps them, confirming removes them; "Nobody holds this role yet" when empty

## Phase 7: User Story 5, administration: locations (P2)

Independent test: `vendor/bin/pest tests/Browser/Settings/Administration/LocationTest.php` passes.

- [X] T019 [US5] tests/Browser/Settings/Administration/LocationTest.php: with `PermissionEnum::LocationManage`, "Add a location" then "Add the location" adds it; an empty name shows an error; editing with "Save changes" updates the table; one test at phone width (R8); a user without the permission is refused (FR-005)
- [X] T020 [US5] tests/Browser/Settings/Administration/LocationTest.php: "Archive it" asks "Archive this location?", cancel keeps it active, confirming moves it to the archived filter, from where "Reopen this location" restores it
- [X] T021 [US5] tests/Browser/Settings/Administration/LocationTest.php: the search field filters by name, city and country, and a search matching nothing shows "No location matches this search"
- [X] T022 [US5] tests/Browser/Settings/Administration/LocationTest.php: "Assign" adds a colleague; a staff member of another company is not offered; "Take them off" asks "Take this person off the location?", cancel keeps them, confirming removes them

## Phase 8: Polish

- [X] T023 For each defect a test exposed, fix it in its own commit with a feature or unit test, and list it for the pull request (FR-008)
- [X] T024 Run `vendor/bin/pest --testsuite=Browser` three times in a row, all green, and compare the duration with the 3.5 s baseline (SC-003, SC-004)
- [X] T025 Break the confirming button of resources/views/app/settings/administration/roles/_delete-role.blade.php locally, confirm a test fails, revert (SC-002)
- [X] T026 Run `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` and `php artisan jacques:localize`; confirm no class under tests/Browser

## Dependencies

- T001 before T002; T002 and T003 before every story.
- Stories 1 to 5 are independent of each other after Phase 2.
- Tasks sharing a file run in order (T006 and T007, T011 and T012, T013 and T014, T015 and T016, T019 to T022); [P] marks tasks parallel to other files.
- Phase 8 after every story.

## Parallel Execution

- After T003: T004, T006, T008, T009, T010, T011, T013, T015, T017, T018, T019 can start together, one per file.

## Implementation Strategy

MVP is Phase 3 (the settings home, and the proof that signing in works). Then the account stories (P1), then administration (P2), each file green before the next commit.
