# Tasks: Salon Onboarding

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`; each task writes or updates the test that mirrors its file.

## Phase 1: Setup (schema)

- [X] T001 After `is_welcome_screen_pending` in database/migrations/0000_01_01_000000_create_companies_table.php add `$table->boolean('is_onboarding_pending')->default(false)->comment('whether the company still has to go through the onboarding');` and `$table->unsignedTinyInteger('onboarding_step')->default(1)->comment('furthest onboarding step the company reached, from 1 to 4');` (edit in place, no new migration)

## Phase 2: Foundational (blocks every story)

- [X] T002 [P] Create app/Enums/OnboardingStepEnum.php, int backed: `Location = 1`, `Services = 2`, `Staff = 3`, `Appointments = 4`, with `label()` (translated "Location", "Services", "Staff", "Appointments") and `routeName()` (`onboarding.location.index`, `onboarding.services.index`, `onboarding.staff.index`, `onboarding.appointments.index`); test in tests/Unit/Enums/OnboardingStepEnumTest.php
- [X] T003 [P] Add `OnboardingCompleted = 'onboarding_completed'` with label "Completed the onboarding" to app/Enums/UserActionEnum.php; update its test in tests/Unit/Enums/
- [X] T004 In app/Models/Company.php add `@property bool $is_onboarding_pending` and `@property OnboardingStepEnum $onboarding_step`, both in `$fillable`, cast to `boolean` and `OnboardingStepEnum`; add an `onboardingPending()` state to database/factories/CompanyFactory.php; assert both casts in tests/Unit/Models/CompanyTest.php
- [X] T005 Pass `'is_onboarding_pending' => true` and `'onboarding_step' => OnboardingStepEnum::Location` in `createCompany()` of app/Actions/CreateCompany.php; assert it in tests/Unit/Actions/CreateCompanyTest.php
- [X] T006 Create app/Actions/CompleteOnboardingStep.php (`author: User`, `step: OnboardingStepEnum`; on `$author->company`, does nothing when not pending; below `Appointments` raises `onboarding_step` to `max(current, step + 1)`; on `Appointments` sets `is_onboarding_pending` to false and dispatches `LogUserAction` with `OnboardingCompleted` on the `low` queue) with tests/Unit/Actions/CompleteOnboardingStepTest.php covering raising, never lowering, finishing and a company not pending
- [X] T007 Create app/Http/Middleware/OnboardingStepGate.php (`handle($request, $next, ?string $step = null)`: redirect to `home.index` when `! $user->isOwner()` or the company is not pending; redirect to the saved step's `routeName()` when `$step` names a step beyond `onboarding_step`; otherwise pass) and register the alias `onboarding.step` in bootstrap/app.php
- [X] T008 Create app/ViewModels/Onboarding/StepViewModel.php (`user: User`, `step: OnboardingStepEnum`): `salonName()`, `stepLabel()`, `stepCounter()` ("Step :number of :total"), `steps()` returning per step `number`, `label`, `url` (null beyond the saved step), `isDone` (below the saved step), `isCurrent`; with tests/Unit/ViewModels/Onboarding/StepViewModelTest.php
- [X] T009 Create resources/views/app/onboarding/_header.blade.php from the design: `x-breadcrumb` "Getting started / {step}", eyebrow "Onboarding · {counter}", `h1` "Welcome to :name", subtitle "Four short steps, and your salon can take its first appointment.", "Finish later" as an `a` styled like `x-button.secondary` to `home.index`, and the tracker as an `ol` of four `li` (`aria-current="step"` on the current one, an `sr-only` "done" beside the tick, `accent-tile` tile for the current step, ink tile for done, hairline ring for upcoming, connector hidden after the last); `viewModel` passed explicitly

## Phase 3: User Story 1, a new salon owner sets up their salon (P1) MVP

**Independent test**: sign up, dismiss the welcome screen, submit step 1, check the company name, the single headquarters location and the ticked tracker.

- [X] T010 [P] [US1] Add `private readonly bool $isHeadquarters = false` as the last parameter of app/Actions/CreateLocation.php and write it on create; cover it in tests/Unit/Actions/CreateLocationTest.php
- [X] T011 [US1] Create app/Actions/SetUpSalon.php (`author: User`, `name: string`, `country: string`, `timezone: string`, nullable `region`, `city`, `postalCode`, `addressLine1`, `phone`): in `DB::transaction`, lock the company row with `lockForUpdate()`, run `UpdateCompany` with the new name and the company's current `legal_name`, `website_url`, `currency`, `billing_email`; find the first location (headquarters, else the one named like the salon); run `UpdateLocation` with `isHeadquarters: true` and its current `address_line_2` and `email`, or `CreateLocation` with `isHeadquarters: true`; then `CompleteOnboardingStep(step: Location)`; with tests/Unit/Actions/SetUpSalonTest.php covering first submission, second submission updating the same location, the company renamed, and step 2 reached
- [X] T012 [P] [US1] Create app/ViewModels/Onboarding/LocationViewModel.php extending `StepViewModel`: `isSetUp()` (saved step beyond `Location`), `form()` (old input, else the headquarters values, else the company name), `countries()` (codes from `DateTimeZone::listIdentifiers()` locations, named with `Locale::getDisplayRegion()` in the app locale, sorted with `Collator`), `timezonesByCountry()` (`DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $code)` labelled with `IntlTimeZone::getDisplayName()` generic name and the identifier's city); with tests/Unit/ViewModels/Onboarding/LocationViewModelTest.php
- [X] T013 [US1] Create app/Http/Controllers/App/Onboarding/LocationController.php: `index` returns `app.onboarding.location` with `LocationViewModel`; `update` validates `name` required string max:255 with the message "The salon needs a name.", `address_line_1`, `city`, `region` nullable string max:255, `postal_code` nullable string max:20, `phone` nullable string max:50, `country` required string size:2 in the country list, `timezone` required string in `DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, country)`; runs `SetUpSalon` and redirects to `onboarding.location.index`
- [X] T014 [US1] In routes/web.php add a commented group with `auth`, `set.locale` and `welcome.screen` holding `GET onboarding/location` (`onboarding.location.index`) and `PUT onboarding/location` (`onboarding.location.update`), both with `onboarding.step:location`
- [X] T015 [US1] Create resources/views/app/onboarding/location.blade.php: `x-top-bar-layout`, the header partial, then with Alpine `editing` state either the confirmation card (":name is ready", the paragraph from the design, "Continue to services" link to `onboarding.services.index`, "Edit the salon" button) or `x-form` method put to `onboarding.location.update` titled "Set up your salon" with the shop icon tile, fields in the design order, required marks, country and time zone `select` styled like resources/views/app/settings/administration/locations/_edit-location.blade.php, time zone disabled until a country is chosen and reset to the country's first zone on change, help "Used to schedule appointments at the correct local time." tied by `aria-describedby`, and `x-button` "Create my salon" with an `aria-hidden` arrow
- [X] T016 [US1] Change the redirect of `update` in app/Http/Controllers/App/WelcomeController.php to `onboarding.index`; update tests/Feature/Controllers/App/WelcomeControllerTest.php
- [X] T017 [US1] Create tests/Feature/Controllers/App/Onboarding/LocationControllerTest.php covering: the owner sees the form prefilled with the company name; submitting creates one headquarters location and renames the company; an empty name shows "The salon needs a name."; a time zone outside the country is refused; a second submission updates the same location; a user who is not the owner and a finished company are redirected home; a company with the welcome screen pending is sent to `welcome`

## Phase 4: User Story 2, the owner leaves and comes back where they stopped (P1)

**Independent test**: reach step 2, press "Finish later", sign out and in, open `/onboarding`, land on step 2; open step 4, land on step 2.

- [X] T018 [P] [US2] Create app/Http/Controllers/App/Onboarding/OnboardingController.php whose `index` redirects to the saved step's `routeName()`
- [X] T019 [P] [US2] Create app/Http/Controllers/App/Onboarding/ServiceController.php and app/Http/Controllers/App/Onboarding/StaffController.php: `index` returns `app.onboarding.services` or `app.onboarding.staff` with `StepViewModel`; `update` runs `CompleteOnboardingStep` with its step and redirects to the next step's route
- [X] T020 [P] [US2] Create resources/views/app/onboarding/services.blade.php and resources/views/app/onboarding/staff.blade.php: `x-top-bar-layout`, the header partial, a placeholder card saying the step is coming, and `x-form` method put with `x-button` "Continue"
- [X] T021 [US2] In the onboarding group of routes/web.php add `GET onboarding` (`onboarding.index`, `onboarding.step`), `GET`/`PUT onboarding/services` (`onboarding.services.index`/`update`, `onboarding.step:services`) and `GET`/`PUT onboarding/staff` (`onboarding.staff.index`/`update`, `onboarding.step:staff`)
- [X] T022 [US2] Create tests/Feature/Controllers/App/Onboarding/OnboardingControllerTest.php, ServiceControllerTest.php and StaffControllerTest.php in tests/Feature/Controllers/App/Onboarding/ covering: `onboarding` lands on the saved step; a step beyond the saved one redirects to it; a step below it shows; "Finish later" links home; completing a step saves the next one; not owner and finished company redirect home

## Phase 5: User Story 3, the onboarding is finished once and for all (P2)

**Independent test**: complete steps 1 to 4, land on home, and every onboarding URL then leads home.

- [X] T023 [P] [US3] Create app/Http/Controllers/App/Onboarding/AppointmentController.php: `index` returns `app.onboarding.appointments` with `StepViewModel`; `update` runs `CompleteOnboardingStep(step: Appointments)` and redirects to `home.index`
- [X] T024 [P] [US3] Create resources/views/app/onboarding/appointments.blade.php like the other placeholder steps, with `x-button` "Finish"
- [X] T025 [US3] In the onboarding group of routes/web.php add `GET`/`PUT onboarding/appointments` (`onboarding.appointments.index`/`update`, `onboarding.step:appointments`)
- [X] T026 [US3] Create tests/Feature/Controllers/App/Onboarding/AppointmentControllerTest.php covering: finishing marks the company not pending and redirects home; afterwards `onboarding`, `onboarding/location` and `onboarding/appointments` redirect home for every user

## Phase 6: Polish

- [X] T027 Run `php artisan jacques:localize` and translate every reported string in all lang/*.json locales
- [X] T028 Run `vendor/bin/pint -q`, `vendor/bin/phpstan analyse --no-progress -q` and `php artisan test --compact`; fix every finding
- [X] T029 Run the quickstart.md scenarios on desktop, tablet and phone width, light and dark, keyboard only and with a screen reader

## Dependencies

- T001, then Phase 2: T002 and T003 first, then T004, T005, T006; T007 and T008 need T002; T009 needs T008.
- US1 needs Phase 2. T011 needs T010. T013 needs T011 and T012. T014 needs T013. T015 needs T009 and T012. T017 needs T014 to T016.
- US2 needs Phase 2; its acceptance scenario 6 in US1 ("Continue to services") needs US2. T021 needs T018 and T019. T022 needs T020 and T021.
- US3 needs Phase 2 and the routes of US2. T026 needs T023 to T025.
- Phase 6 comes last.

## Parallel examples

- Phase 2: T002 with T003.
- US1: T010 with T012.
- US2: T018, T019 and T020 together.
- US3: T023 with T024.

## Strategy

MVP is Phase 1, Phase 2 and US1: a new owner sets up their salon and first location. US2 then makes the flow resumable with placeholder steps, and US3 closes it. Each story is merged only with Phase 6 run.
