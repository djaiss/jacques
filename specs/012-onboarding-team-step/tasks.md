# Tasks: Onboarding Step 3, Your Team

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Create database/migrations/2026_10_15_000002_create_staff_invitations_table.php: `staff_id` "bigint, unique", `role_id` bigint, `author_id` "bigint, nullable", timestamps, each with `->comment()`, only `up()`, foreign keys declared at the bottom (staff and roles cascade on delete, users null on delete), per the migrations skill

## Phase 2: Foundational (invitation record, team rules, owner on the team, page shell)

- [X] T002 Create app/Models/StaffInvitation.php (`#[Fillable]`, belongs to staff, role and author, full model docblocks) and database/factories/StaffInvitationFactory.php; add `invitation()` "has one `StaffInvitation`" to app/Models/Staff.php
- [X] T003 [P] Write tests/Unit/Models/StaffInvitationTest.php (each relationship exists) and add the `invitation()` case to tests/Unit/Models/StaffTest.php
- [X] T004 Make app/Actions/CreateStaff.php and app/Actions/UpdateStaffInformation.php refuse, with `InvalidArgumentException`, an email "unique among the staff of a company, lower case"; make UpdateStaffInformation delete the staff member's invitation when the email becomes empty (R1, R4); cover both in tests/Unit/Actions/CreateStaffTest.php and UpdateStaffInformationTest.php, including Dwight and Mose in two companies sharing an email without refusal
- [X] T005 Create app/Actions/SyncStaffLocationServices.php (author, staff, location, `array<int, int>` of location service ids; refuses a service not offered at the location or a staff member not assigned there with ModelNotFoundException; adds and removes the difference; logs once) and add `StaffServicesUpdated` to app/Enums/UserActionEnum.php, tested in tests/Unit/Actions/SyncStaffLocationServicesTest.php
- [X] T006 Make app/Actions/OfferServicesAtLocation.php return the `Collection<int, LocationService>` it created, and app/Actions/CompleteOnboardingStep.php, for the `Services` step, assign the author's staff record to the headquarters with AssignLocation and give it the services just offered (R2); update its class docblock and tests/Unit/Actions/OfferServicesAtLocationTest.php and CompleteOnboardingStepTest.php (Michael joins Scranton with every service; returning to step 2 and continuing does not give back a service he removed)
- [X] T007 Create app/ViewModels/Onboarding/TeamViewModel.php extending StepViewModel: active staff of the company in position order with the author's record first, headquarters services and categories eager loaded once, `rows()` (display name, full name when it differs, subtitle, services summary and account state per data-model.md, avatar hue), `summary()`, `footerStatus()` and `invitationCount()` through `trans_choice()`
- [X] T008 Make app/Http/Controllers/App/Onboarding/StaffController.php `index` use TeamViewModel, and rewrite resources/views/app/onboarding/staff.blade.php from the shared `_header` partial with the title "Who works at :salon?" and the design's introduction, plus resources/views/app/onboarding/_team-footer.blade.php copying the layout of _catalogue-footer.blade.php (in the page flow under a top rule, not sticky, spec FR-014) with "I'll set this up later" and "Continue to appointments" both submitting the PUT form
- [X] T009 [P] Write tests/Unit/ViewModels/Onboarding/TeamViewModelTest.php and update tests/Feature/Controllers/App/Onboarding/StaffControllerTest.php (index renders, both footer buttons open step 4, a non owner is refused), then run `php artisan test --compact`, which must be green

## Phase 3: User Story 1, the owner adds the people who work with them (P1) MVP

Independent test: add Jim with three services, add Pam as not bookable, reload, continue.

- [X] T010 [US1] Add `onboarding.staff.members.new` and `onboarding.staff.members.create` to routes/web.php per contracts/routes.md, with `onboarding.step:3`
- [X] T011 [US1] Create app/ViewModels/Onboarding/StaffPanel.php (eyebrow, title, field values, `isOwnRecord`, `isNew`, categories with their headquarters services and selected ids, counts "n of m", "n of N services selected" or "All N services selected" through `trans_choice()`, form action and method) returned by TeamViewModel when `openStaff` or `newStaff` is set
- [X] T012 [US1] Create app/Http/Controllers/App/Onboarding/Team/StaffController.php `new` (the step with an empty panel) and `create`, validating the panel form exactly as contracts/routes.md lists it, then in one transaction CreateStaff, AssignLocation to the headquarters and SyncStaffLocationServices; a duplicate email returns `back()->withErrors(['email' => ...])`
- [X] T013 [US1] Create resources/views/app/onboarding/_team-row.blade.php (avatar initials, name, "You" badge, subtitle, services summary with "None yet" in warning colour and text, account state, edit icon, the whole row a link to the edit address) and the list card with "Add a team member" in the heading and at the bottom, in staff.blade.php
- [X] T014 [US1] Create resources/views/app/onboarding/_staff-panel.blade.php inside `x-side-modal`: first name with "A first name is needed.", last name, name shown to clients with the first name as placeholder and its help, job title, email, phone, bio; the bookable checkbox; the services list by category with category checkboxes, counts, "Select all" and "Clear", hidden with `x-show` (not removed) when unbookable, with the kept services note; "Cancel" and "Add to the team"
- [X] T015 [US1] Write tests/Feature/Controllers/App/Onboarding/Team/StaffControllerTest.php for `new` and `create`: Jim created at Scranton with three services, empty first name refused, duplicate email refused, a location service of another company refused, step 2 company redirected

## Phase 4: User Story 2, the owner edits and removes team members (P1)

Independent test: open Dwight, set "Dwight K.", untick a category, save; remove Andy and confirm.

- [X] T016 [US2] Add `onboarding.staff.members.edit`, `update` and `destroy` to routes/web.php, with `whereNumber('staff')`
- [X] T017 [US2] Add `edit`, `update` (UpdateStaffInformation then SyncStaffLocationServices in one transaction; the email is ignored for the author's own record) and `destroy` (DestroyStaff; 404 for the author's own record) to app/Http/Controllers/App/Onboarding/Team/StaffController.php
- [X] T018 [US2] Extend _staff-panel.blade.php for an existing member: "Edit team member" or "Your profile" eyebrow, read only email with its explanation on the owner's record, "Save changes", and "Remove from the team" revealing the sentence, "Remove them" and "Keep them" in the panel (R8)
- [X] T019 [US2] Extend tests/Feature/Controllers/App/Onboarding/Team/StaffControllerTest.php: edit renders, update with a display name and fewer services, the owner's email unchanged, destroy removes Andy with his services, the owner's own record and another company's staff member return 404

## Phase 5: User Story 3, the owner prepares invitations for colleagues (P2)

Independent test: prepare Dwight's invitation as Manager, preview it, reload, check no email is sent.

- [X] T020 [P] [US3] Create app/Actions/PrepareStaffInvitation.php (author, staff, role; refuses without an email or with a linked account; role and staff of the author's company else ModelNotFoundException; creates the row or changes its role) and app/Actions/DestroyStaffInvitation.php, with `StaffInvitationPrepared` and `StaffInvitationDeleted` in app/Enums/UserActionEnum.php, tested in tests/Unit/Actions/PrepareStaffInvitationTest.php and DestroyStaffInvitationTest.php
- [X] T021 [US3] Make `create` and `update` of app/Http/Controllers/App/Onboarding/Team/StaffController.php run PrepareStaffInvitation when `invite` is true with an email and `role` "integer, required when `invite` is true", and DestroyStaffInvitation otherwise; never for the author's own record
- [X] T022 [US3] Add to StaffPanel the roles of the company by id with default names through `__()`, Staff selected by default, the invitation state, the help sentences, and the preview sentences with `:first_name`, `:inviter`, `:salon` and `:job_title` placeholders (R5, R6)
- [X] T023 [US3] Add to _staff-panel.blade.php the account section: the invitation checkbox disabled without an email, the help, the role `<select>` styled as `x-input` shown while ticked, "Preview the email", and the preview dialog filled by Alpine from the typed fields, closing on its button, its close control, Escape or the backdrop and returning focus (R6)
- [X] T024 [US3] Extend tests/Feature/Controllers/App/Onboarding/Team/StaffControllerTest.php: Dwight invited as Manager shows "Invitation ready", a role of another company returns 404, unticking deletes it, no email deletes it, and `Mail::fake()` asserts nothing is sent when the step completes

## Phase 6: User Story 4, the owner postpones the team (P3)

Independent test: choose "I'll set this up later" on a salon of one person.

- [X] T025 [US4] Make `footerStatus()` of TeamViewModel say the team can be added now or later from Settings for a team of one, with a case in tests/Unit/ViewModels/Onboarding/TeamViewModelTest.php and a "later" case in tests/Feature/Controllers/App/Onboarding/StaffControllerTest.php

## Phase 7: Polish

- [X] T026 Run `php artisan jacques:localize` and translate every reported string in each lang/*.json locale, then `php artisan jacques:localize --check`
- [X] T027 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [X] T028 Run every check and the manual walk of quickstart.md, at desktop, tablet and phone widths, keyboard only

## Dependencies

- T001 before Phase 2. Phase 2 blocks every story. US1 provides the panel and controller that US2 and US3 extend.
- US2 and US3 depend only on US1; they share routes/web.php, the Team StaffController and _staff-panel.blade.php, so run them one after the other. US4 needs only Phase 2.
- Parallel examples: T003 with T004; T005 with T007; T020 alongside US2; T025 at any point after Phase 2.

## Implementation Strategy

MVP is Phases 1 to 3: the invitation table, the team rules, the owner on the team, then adding members with their services. Each later phase ships on its own with the suite green.
