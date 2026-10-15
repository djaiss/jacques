# Tasks: Welcome Screen

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`; each task writes or updates the test that mirrors its file.

## Phase 1: Setup (schema)

- [X] T001 Add `$table->boolean('is_welcome_screen_pending')->default(false)->comment('whether the company still has to see the welcome screen shown after signing up');` after `trial_ends_at` in database/migrations/0000_01_01_000000_create_companies_table.php (edit in place, no new migration)

## Phase 2: Foundational (blocks the story)

- [X] T002 In app/Models/Company.php add `@property bool $is_welcome_screen_pending`, add the column to `$fillable` and cast it to `boolean`; add a `welcomeScreenPending()` state to database/factories/CompanyFactory.php; assert the cast in tests/Unit/Models/CompanyTest.php
- [X] T003 [P] Add `WelcomeScreenDismissed = 'welcome_screen_dismissed'` with label "Dismissed the welcome screen" to app/Enums/UserActionEnum.php; update its test in tests/Unit/Enums/

## Phase 3: User Story 1, a new salon owner is welcomed once (P1) MVP

**Independent test**: sign up, open any signed in screen, see the welcome screen, press "Let's get started", land on home, and never see the welcome screen again.

- [X] T004 [US1] Pass `'is_welcome_screen_pending' => true` in `createCompany()` of app/Actions/CreateCompany.php; assert it in tests/Unit/Actions/CreateCompanyTest.php
- [X] T005 [P] [US1] Create app/Actions/DismissWelcomeScreen.php (`user: User`; sets the flag of `$user->company` to false; dispatches `LogUserAction` with `WelcomeScreenDismissed` on the `low` queue only when the flag was true) with tests/Unit/Actions/DismissWelcomeScreenTest.php covering a pending and an already dismissed company
- [X] T006 [P] [US1] Create app/ViewModels/WelcomeViewModel.php with `firstName()` read from `$user->employee->first_name`, and tests/Unit/ViewModels/WelcomeViewModelTest.php
- [X] T007 [P] [US1] Create app/Http/Middleware/RedirectToWelcomeScreen.php (pass for a guest or a company not pending, otherwise redirect to `welcome.index`) and register the alias `welcome.screen` in bootstrap/app.php
- [X] T008 [US1] Create app/Http/Controllers/App/WelcomeController.php: `index` redirects to `home.index` when the company is not pending, else returns `app.welcome` with `WelcomeViewModel`; `update` runs `DismissWelcomeScreen` and redirects to `home.index`
- [X] T009 [US1] In routes/web.php add a commented group with `auth` and `set.locale` holding `GET welcome` (`welcome.index`) and `PUT welcome` (`welcome.update`); add `welcome.screen` to `home.index` and to the three existing `auth` groups; leave routes/auth.php untouched
- [X] T010 [US1] Create resources/views/app/welcome.blade.php from the design: `x-guest-layout` titled "Welcome to Jacques", `x-logo`, eyebrow, `h1` "Hi :name, welcome to Jacques.", subtitle, the five letter paragraphs in an `x-box`, signature with `x-avatar-initials` name "Regis" (neutral tone, `aria-hidden`) and "Creator of Jacques", then `x-form` method put to `welcome.update` with `x-button` "Let's get started" and an `aria-hidden` arrow icon; every string through `__()`, section comments per the views skill
- [X] T011 [US1] Create tests/Feature/Controllers/App/WelcomeControllerTest.php covering: pending company sees the screen with the first name; a not pending company is redirected from `welcome` to home; `PUT welcome` clears the flag and redirects home; a pending user opening `settings` and home is redirected to `welcome`; a guest still reaches home; the email confirmation notice is not redirected; a guest on `welcome` is sent to sign in
- [X] T012 [US1] Update tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php to assert a new sign up still lands on the email confirmation notice

## Phase 4: Polish

- [X] T013 Run `php artisan jacques:localize` and translate every reported string in all lang/*.json locales
- [X] T014 Run `vendor/bin/pint -q`, `vendor/bin/phpstan analyse --no-progress -q` and `php artisan test --compact`; fix every finding
- [ ] T015 Run the quickstart.md scenarios on desktop, tablet and phone width, light and dark, keyboard only and with a screen reader

## Dependencies

- T001, then T002 and T003. US1 needs Phase 2.
- T004 to T007 are independent of each other. T008 needs T005 and T006. T009 needs T007 and T008. T010 needs T008. T011 needs T009 and T010.
- Phase 4 comes last.

## Parallel examples

- Phase 2: T002 with T003.
- US1: T005, T006 and T007 together, after T004 or alongside it.

## Strategy

There is one story, so the MVP is the whole feature: T001 to T012, then the polish phase before the pull request.
