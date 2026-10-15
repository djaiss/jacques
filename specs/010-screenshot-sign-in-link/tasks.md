# Tasks: Screenshot Sign In Link

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/screenshot-link.md](contracts/screenshot-link.md), [quickstart.md](quickstart.md)

**Tests**: required by FR-013 and constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Add `screenshot_link_enabled` (`JACQUES_SCREENSHOT_LINK_ENABLED`, default `false`) and `screenshot_link_duration_minutes` (`JACQUES_SCREENSHOT_LINK_DURATION_MINUTES`, default `5`) in a commented section of config/jacques.php, and both variables in .env.example with the setting `false`

## Phase 2: Foundational

- [X] T002 Create app/Helpers/ScreenshotLink.php with static `isAvailable()` (setting on and not production) and `isDevelopmentHost(string $host)` per research R5
- [X] T003 [P] Create tests/Unit/Helpers/ScreenshotLinkTest.php covering setting off, production, and each development and non development host

## Phase 3: User Story 1 - Capture a screen behind the sign in (P1) MVP

- [X] T004 [US1] Create app/Http/Controllers/App/Auth/ScreenshotLinkController.php with `show()`: 404 unless available and the request host is a development host, 404 for an unknown lower cased address, else log in, regenerate the session and redirect to `to` when it is a local path per research R6, `/` otherwise
- [X] T005 [US1] Register `GET screenshot-link` named `auth.screenshotLink.show` with `signed` and `set.locale` middleware in routes/auth.php
- [X] T006 [US1] Create app/Console/Commands/ScreenshotLinkCommand.php, `jacques:screenshot-link {email} {--to=/}`, printing the link from `URL::temporarySignedRoute()` and a line naming the account and lifetime
- [X] T007 [P] [US1] Create tests/Feature/Controllers/App/Auth/ScreenshotLinkControllerTest.php covering sign in as Michael Scott, landing on `to`, landing on `/` by default, and replacing an existing session
- [X] T008 [P] [US1] Create tests/Unit/Console/Commands/ScreenshotLinkCommandTest.php covering the printed link and the success message

## Phase 4: User Story 2 - Refusals (P1)

- [X] T009 [US2] Add to ScreenshotLinkCommand the refusals of contracts/screenshot-link.md, in order: unavailable, non development `APP_URL` host, unknown address, each with exit 1 and no link
- [X] T010 [P] [US2] Cover each command refusal in tests/Unit/Console/Commands/ScreenshotLinkCommandTest.php
- [X] T011 [P] [US2] Cover setting off, production, non development request host and unknown address (all 404) in tests/Feature/Controllers/App/Auth/ScreenshotLinkControllerTest.php

## Phase 5: User Story 3 - Expired or altered links (P2)

- [X] T012 [US3] Cover an expired link, an edited `email`, an edited `to` (all 403), and `to` values `//dundermifflin.com`, `https://dundermifflin.com` and `settings` landing on `/` in tests/Feature/Controllers/App/Auth/ScreenshotLinkControllerTest.php

## Phase 6: User Story 4 - Screenshot instructions, The Office only (P3)

- [X] T013 [US4] Rewrite the sign in section of .claude/skills/take-screenshot/SKILL.md: host `http://jacques.test`, address `michael.scott@dundermifflin.com`, screen `/settings/administration/locations`, and the conditions from contracts/screenshot-link.md
- [X] T014 [US4] Search tests, database, .claude and specs for Friends references and replace any with The Office (US)

## Phase 7: Polish

- [X] T015 Run `php artisan jacques:localize` and translate anything reported in every locale
- [X] T016 Run Pint, PHPStan and the full suite, and fix what they report
- [X] T017 Run the scenarios of quickstart.md against `http://jacques.test`

## Dependencies

- T001 → T002 → US1 → US2 → US3. US4 (T013, T014) depends only on the command name and can run any time.
- Tests marked [P] in a phase touch a different file from that phase's implementation task.

## Strategy

MVP is Phases 1 to 4: US1 and US2 ship together, since a link without its refusals must not exist.
