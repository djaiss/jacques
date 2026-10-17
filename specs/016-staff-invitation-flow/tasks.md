# Tasks: Staff invitation flow

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Edit database/migrations/2026_10_15_000002_create_staff_invitations_table.php in place: add `token_hash` "string(64), nullable, unique", `sent_at`, `expires_at`, `accepted_at`, `revoked_at` "timestamp, nullable", each with `->comment()`, only `up()`
- [X] T002 [P] Add `staff_invitation_duration_days` (7) to config/jacques.php, next to `magic_link_duration_minutes`

## Phase 2: Foundational (states, copy, email, sending)

- [X] T003 Create app/Enums/StaffInvitationStatusEnum.php (`Prepared`, `Pending`, `Accepted`, `Expired`, `Revoked`, translatable `label()`, implementing `Translatable`); add `StaffInvitationSent`, `StaffInvitationRevoked`, `StaffInvitationAccepted` to app/Enums/UserActionEnum.php and `StaffInvitation = 'staff_invitation'` to app/Enums/EmailTypeEnum.php
- [X] T004 Update app/Models/StaffInvitation.php: fillable and `datetime` casts for the five new columns, `status()` in the order of research R2, `isUsable()` (Pending), docblock rewritten now that sending exists; add states `sent()`, `expired()`, `accepted()`, `revoked()` to database/factories/StaffInvitationFactory.php
- [X] T005 [P] Extend tests/Unit/Models/StaffInvitationTest.php with each state of `status()` and `isUsable()`, and write tests/Unit/Enums/StaffInvitationStatusEnumTest.php for the labels
- [X] T006 Make app/Actions/PrepareStaffInvitation.php clear `token_hash`, `sent_at`, `expires_at`, `revoked_at` when it prepares over a Revoked row, and refuse an Accepted one; extend tests/Unit/Actions/PrepareStaffInvitationTest.php
- [X] T007 Make app/Actions/UpdateStaffInformation.php reset `token_hash`, `sent_at`, `expires_at` when the email changes to another address (research R11); extend tests/Unit/Actions/UpdateStaffInformationTest.php (Jim's Pending invitation goes back to Prepared)
- [X] T008 Create app/Helpers/StaffInvitationCopy.php returning the email strings of contracts/routes.md (subject, greeting, body with inviter and salon, purpose, Salon and Your role labels, "Accept invitation", expiry through `trans_choice()` with `:count` in every segment, ignore sentence), tested in tests/Unit/Helpers/StaffInvitationCopyTest.php
- [X] T009 Make `preview()` of app/ViewModels/Onboarding/StaffPanel.php read StaffInvitationCopy and change its `when` sentence to say the invitation is sent at the end of onboarding or from Settings; update resources/views/app/onboarding/_staff-panel.blade.php for the role and expiry rows and the `preview()` case in tests/Unit/ViewModels/Onboarding/TeamViewModelTest.php, where the panel is already tested
- [X] T010 Create app/Mail/StaffInvitationMail.php (implements `HasEnvelope`, like MagicLinkCreatedMail; url, inviter name, salon, role, days) and resources/views/mail/staff-invitation.blade.php with one button, tested in tests/Unit/Mail/StaffInvitationMailTest.php (subject, Michael, Dunder Mifflin, Staff, the URL in `render()`)
- [X] T011 Create app/Actions/SendStaffInvitation.php (author, staff): authorize `RoleManage` for the staff's company; refuse while `is_onboarding_pending` (R5), when Accepted or Revoked, when the role is of another company, and when `RateLimiter` key `staff-invitation:{staff id}` exceeds 3 per 86400 seconds (`ValidationException`); in a transaction `lockForUpdate`, skip silently a send younger than 60 seconds (R4), draw `Str::random(64)`, store its sha256, `sent_at`, `expires_at`; dispatch SendEmail with `afterCommit()` on `high` to `staff.email` and LogUserAction on `low`
- [X] T012 Write tests/Unit/Actions/SendStaffInvitationTest.php: Prepared, Pending and Expired become Pending with a new hash; two sends within 60 seconds queue one email; a fourth send in 24 hours throws; Accepted, Revoked, onboarding pending and another company's user are refused; `Queue::assertPushedOn('high', SendEmail::class)`

## Phase 3: User Story 1, Sophie accepts with a new account (P1) MVP

Independent test: send Pam's invitation, open the link signed out, set a password, land on the dashboard welcomed.

- [X] T013 [US1] Create app/Actions/AcceptStaffInvitation.php (token, ?user, ?password): in a transaction `lockForUpdate` the row found by `hash('sha256', token)`; refuse unless Pending, staff active, staff email set and no account; create the user with CreateUser for the staff's company with `email_verified_at` now when no user is given; refuse when the user's email differs from the staff's or the user is of another company; set `staff.user_id`, `roles()->syncWithoutDetaching` the role, `accepted_at` (the hash stays so the page can say "already used"); log `StaffInvitationAccepted` (research R7)
- [X] T014 [US1] Write tests/Unit/Actions/AcceptStaffInvitationTest.php: Pam gets an account holding Staff and linked to her record; accepted, expired, revoked, superseded and deactivated cases throw; a second acceptance of the same token throws; a role of another company throws
- [X] T015 [US1] Create app/ViewModels/Invitation/InvitationViewModel.php: salon name, inviter, staff name and email, role name, and which variant of research R8 or which error case (not found, used, expired, revoked) applies, with tests/Unit/ViewModels/Invitation/InvitationViewModelTest.php
- [X] T016 [US1] Add `invitations.show` and `invitations.create` to routes/web.php per contracts/routes.md (`set.locale`, `throttle:6,1`, token `[A-Za-z0-9]{64}`) and create app/Http/Controllers/App/Invitation/InvitationController.php: `show` renders the view model; `create` validates `password` "required, string, max:255, Password::min(8)" for a new account, runs AcceptStaffInvitation, signs in, regenerates the session, redirects to `dashboard.index` with the welcome `status` flash
- [X] T017 [US1] Create resources/views/app/invitations/show.blade.php (guest layout, salon title "Join :salon", role sentence, read only name and email, password field, "Create account & join team", "Already have a Jacques account? Sign in instead.") and the error cases in the same view, each named by its title and sentence
- [X] T018 [US1] Show the welcome flash ("Welcome to :salon!" and its sentence) in resources/views/app/dashboard.blade.php
- [X] T019 [US1] Write tests/Feature/Controllers/App/Invitation/InvitationControllerTest.php: show renders Pam's salon and role; create makes the account, signs her in and redirects with the flash; a used, expired or revoked link renders its error; a short password is refused

## Phase 4: User Story 2, the owner tracks invitations from Settings (P1)

Independent test: prepare, send, resend and revoke Kevin's invitation from Settings.

- [X] T020 [P] [US2] Create app/Actions/RevokeStaffInvitation.php (author, staff; `RoleManage`; refuses unless Pending; sets `revoked_at`, keeping the hash; logs) with tests/Unit/Actions/RevokeStaffInvitationTest.php
- [X] T021 [US2] Create app/ViewModels/Settings/Administration/TeamViewModel.php: every staff of the company in position order with invitation and role eager loaded, per row its state label, its date sentence, and the actions of the spec US2 table, the company roles for the prepare form, and whether the viewer holds `RoleManage`; test it in tests/Unit/ViewModels/Settings/Administration/TeamViewModelTest.php
- [X] T022 [US2] Add the five Settings routes of contracts/routes.md to routes/web.php with `whereNumber('staff')`, and create in app/Http/Controllers/App/Settings/Administration/ TeamController (index), StaffInvitationController (create with `role_id` "integer, required", destroy), StaffInvitationSendingController and StaffInvitationRevocationController (create), each redirecting to `settings.team.index` with a `status` flash
- [X] T023 [US2] Create resources/views/app/settings/administration/team/index.blade.php and _row.blade.php: name, email and role, state badge with text (not colour alone), date sentence, the state's buttons, a confirmation before Revoke and Cancel, the role select for Prepare new invitation, "Manage access" to `settings.roles.show`; list Team members among the company rows of app/ViewModels/Settings/SettingsViewModel.php (the top bar keeps its six product sections)
- [X] T024 [US2] Write tests/Feature/Controllers/App/Settings/Administration/TeamControllerTest.php and one test file per mutation controller: each state shows its actions; send, resend, revoke, cancel and prepare change the state; a user without `RoleManage` sees no button and is refused; another company's staff returns 404

## Phase 5: User Story 3, sending at the end of onboarding (P2)

Independent test: prepare two invitations in step 3, finish step 4, press Send invitations.

- [X] T025 [US3] Make app/Http/Controllers/App/Onboarding/AvailabilityController.php redirect to `onboarding.completion.index`, and update tests/Feature/Controllers/App/Onboarding/AvailabilityControllerTest.php
- [X] T026 [US3] Create app/ViewModels/Onboarding/CompletionViewModel.php (prepared count through `trans_choice()`, whether the section shows) with tests/Unit/ViewModels/Onboarding/CompletionViewModelTest.php
- [X] T027 [US3] Add the two completion routes of contracts/routes.md and create app/Http/Controllers/App/Onboarding/CompletionController.php (404 while onboarding is pending) and CompletionInvitationController.php (SendStaffInvitation for each Prepared invitation, then redirect to `dashboard.index`)
- [X] T028 [US3] Create resources/views/app/onboarding/completion.blade.php with the "Invite your team" section, the count, "Send invitations" and a link to the dashboard
- [X] T029 [US3] Write tests/Feature/Controllers/App/Onboarding/CompletionControllerTest.php and CompletionInvitationControllerTest.php: two ready shown, none hides the section, sending queues two emails, nothing is queued by finishing step 4

## Phase 6: User Story 4, an existing account or a signed in visitor (P2)

Independent test: open Dwight's link signed in as Dwight, then as Michael.

- [X] T030 [US4] Extend InvitationViewModel and show.blade.php with the R8 variants: sign in prompt with `redirect()->setIntendedUrl()`, single confirmation, wrong account with the invited address, and the other salon refusal of spec US4.4
- [X] T031 [US4] Make `create` of InvitationController accept with the signed in user and no password, and add `invitations.signOut.create` with app/Http/Controllers/App/Invitation/InvitationSignOutController.php
- [X] T032 [US4] Extend tests/Feature/Controllers/App/Invitation/InvitationControllerTest.php and write InvitationSignOutControllerTest.php: Dwight signed in confirms; Michael signed in is shown the switch page and links nothing; an account of another company is refused; sign out returns to the invitation

## Phase 7: Polish

- [X] T033 Run `php artisan jacques:localize` and translate every reported string in each lang/*.json locale, then `php artisan jacques:localize --check`
- [X] T034 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [X] T035 Run `php artisan test --compact` and the manual walk of quickstart.md at desktop, tablet and phone widths, keyboard only

## Dependencies

- Phase 1 before Phase 2. Phase 2 blocks every story, since SendStaffInvitation is how any invitation reaches anybody.
- US1 and US2 depend only on Phase 2. US4 extends US1's view model, view and controller. US3 needs only Phase 2.
- US1, US2 and US3 touch routes/web.php each; add their routes one story at a time.
- Parallel examples: T002 with T001; T005 with T006; T020 alongside US1; US3 alongside US2 apart from routes/web.php.

## Implementation Strategy

MVP is Phases 1 to 3: invitations can be sent (through a test or tinker) and accepted with a new account. US2 makes sending usable by the owner and should ship with it. Each later phase ships on its own with the suite green.
