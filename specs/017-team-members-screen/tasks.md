# Tasks: Team members screen

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Amend D4 in docs/specs/decisions.md: "Une personne porte un seul rôle", drop "cumulables" from the title and the sentence about carrying several roles; keep the owner and the last active owner rules
- [X] T002 [P] Edit database/migrations/2026_08_01_000003_create_user_roles_table.php in place: make `user_id` unique (one role per person), only `up()`

## Phase 2: Foundational (one role per person)

- [X] T003 Make app/Actions/AssignRole.php replace the held role with `roles()->sync([$role->id])`, and refuse with `ModelNotFoundException('Role not found')` when the user is the last active holder of the Owner role and the new role is not Owner (the guard of RemoveRole); update the class docblock
- [X] T004 Extend tests/Unit/Actions/AssignRoleTest.php: Jim moves from Staff to Manager and holds only Manager; assigning Michael, last active owner, to Staff throws; with David Wallace also owner it succeeds
- [X] T005 [P] Replace `syncWithoutDetaching` with `sync` in app/Actions/AcceptStaffInvitation.php and `attach` with `sync` in app/Actions/CreateCompany.php; extend tests/Unit/Actions/AcceptStaffInvitationTest.php (an existing account ends with only the invited role)
- [X] T006 Update every test and factory that gives one user two roles (search `roles()->attach` under tests/ and database/) so the suite passes with the unique index; run `php artisan test --compact`
- [X] T007 Update the people tab of the role editor (resources/views/app/settings/administration/roles/_people.blade.php, _assign-people.blade.php, app/ViewModels/Settings/Administration/RolesViewModel.php) so assigning says it replaces the person's current role; extend tests/Feature/Controllers/App/Settings/Administration/RolePeopleControllerTest.php

## Phase 3: User Story 1, the owner reads the whole team (P1)

Independent test: Michael, Pam (prepared) and Toby (no account) show the right rows and counts Everybody 3, Signs in 1, Invited 1, No account 1.

- [X] T008 [US1] Rewrite `rows()` of app/ViewModels/Settings/Administration/TeamViewModel.php: per active staff `id`, `name`, `email`, `avatarUrl` or initials and hue, `isViewer`, `role` (user role, else invitation role), `filter` (`signs_in`; `invited` for Prepared, Pending, Expired; `no_account` otherwise, research R2), `stateLabel`, `tone`, `manageUrl` (`settings.team.show`); add `filters()` returning key, label and count, the count through `trans_choice('[0,*]:count', …)`
- [X] T009 [US1] Rewrite tests/Unit/ViewModels/Settings/Administration/TeamViewModelTest.php for rows, filter keys, counts, `isViewer`, and another company's staff absent
- [X] T010 [US1] Rewrite resources/views/app/settings/administration/team/index.blade.php and _row.blade.php to the design: header copy, "Only owners can change who sees what.", segmented filters as an Alpine `x-data` with `aria-pressed`, the four column list with avatar, "You" badge, role, state dot plus label (never colour alone), Manage link, and the empty sentence "Nobody matches this filter right now."; `status` flash shown with `x-status`
- [X] T011 [US1] Extend tests/Feature/Controllers/App/Settings/Administration/TeamControllerTest.php: Michael sees every row and the counts; a user without `RoleManage` gets 404

## Phase 4: User Story 2, account actions in a side panel (P1)

Independent test: on Toby's panel prepare, send, resend and cancel the invitation; on Jim's panel remove the account after confirming.

- [X] T012 [US2] Add `settings.team.show` to routes/web.php (`whereNumber('staff')`) and `show(Request, int $staff)` to app/Http/Controllers/App/Settings/Administration/TeamController.php: authorize like `index`, `findOrFail` the staff in the company, render index with the panel of that staff
- [X] T013 [US2] Add `panel(Staff)` to TeamViewModel returning the `account` block of contracts/routes.md: state, label, sentence (reuse the existing sentences), and the actions of the spec US2 table with url, method and `confirm`; Signs in offers removal only when the viewer is an owner and not that person
- [X] T014 [US2] Create resources/views/app/settings/administration/team/_panel.blade.php with `x-side-modal` (`labelledby`, close link back to `settings.team.index`, focus returned to Manage), header "Manage access", name, email, and the Account section; Cancel and Remove use `x-confirm-dialog` with the spec sentence "They can no longer sign in, but they stay on the team and can still be booked."; errors under `panel` shown in it
- [X] T015 [US2] Make app/Http/Controllers/App/Settings/Administration/StaffInvitationController.php and StaffInvitationSendingController.php redirect to `settings.team.show` with their flash and refusals under `panel`; the sending controller accepts an optional `role_id` "nullable, integer" of the company
- [X] T016 [US2] Done in StaffInvitationSendingController instead of the action: with a `role_id`, it runs PrepareStaffInvitation then SendStaffInvitation in one transaction; tested in StaffInvitationSendingControllerTest.php (Toby is sent as Manager)
- [X] T017 [US2] Make app/Actions/DestroyUser.php delete the staff invitation of the linked staff record in the same transaction as the user (research R5); extend tests/Unit/Actions/DestroyUserTest.php (Jim's staff record stays, bookable, with no invitation and `user_id` null); the user is force deleted so the address is free for a new invitation
- [X] T018 [US2] Add `settings.teamAccounts.destroy` to routes/web.php and create app/Http/Controllers/App/Settings/Administration/TeamAccountController.php: `destroy` runs DestroyUser on the staff's user (404 without one) and redirects to `settings.team.show`
- [X] T019 [US2] Remove `settings.staffInvitationRevocations.create`, StaffInvitationRevocationController.php and tests/Feature/Controllers/App/Settings/Administration/StaffInvitationRevocationControllerTest.php; keep app/Actions/RevokeStaffInvitation.php
- [X] T020 [US2] Write tests/Feature/Controllers/App/Settings/Administration/TeamAccountControllerTest.php and extend TeamControllerTest.php, StaffInvitationControllerTest.php, StaffInvitationSendingControllerTest.php: the panel renders per state, redirects land on the panel, Michael cannot remove his own account, another company's staff is 404
- [X] T021 [US2] Extend TeamViewModelTest.php with `panel()` actions for each state of the spec US2 table

## Phase 5: User Story 3, the owner sets a person's role (P2)

Independent test: move Jim from Staff to Manager, save, and see Manager on his row.

- [X] T022 [US3] Add to `panel()` the `roles` (id, name, description, selected, disabled when the person is the last active owner), `permissions` per role id (groups by PermissionGroupEnum, label and scope as RolesViewModel shows them, role editor url) and `isSoleOwner` of contracts/routes.md; extend TeamViewModelTest.php
- [X] T023 [US3] Add the Role and Permissions sections to _panel.blade.php: role cards as a radio group (`role="radiogroup"`, native radios, selected shown by border and mark), the sole owner `x-notice`, read only permission groups swapped in Alpine per selected card, and Cancel and Save changes; Save shows only with an account or an invitation, otherwise the radio value is posted with the invitation buttons (research R4)
- [X] T024 [US3] Add `settings.teamRoles.update` to routes/web.php and create app/Http/Controllers/App/Settings/Administration/TeamRoleController.php: validate `role_id` "required, integer", find the role in the company, run AssignRole on the user, else PrepareStaffInvitation on the invitation, else 404; redirect to `settings.team.index` with "The access of :name is saved."; a last owner refusal returns under `panel`
- [X] T025 [US3] Write tests/Feature/Controllers/App/Settings/Administration/TeamRoleControllerTest.php: Jim saved as Manager; Pam's prepared invitation becomes Manager; Michael, sole owner, cannot become Staff; a role of another company is 404; a user without `RoleManage` gets 404

## Phase 5b: Tabs with their own address (asked during implementation)

- [X] T029 [US1] Replace the Alpine filters by routes `settings.teamSignsIn.index`, `settings.teamInvited.index`, `settings.teamNoAccount.index`, controllers Team{SignsIn,Invited,NoAccount}Controller and view models Team{SignsIn,Invited,NoAccount}ViewModel extending TeamViewModel (`tabs()` with url and isCurrent); tests per controller and view model, browser test follows the links

## Phase 6: Polish

- [X] T026 Run `php artisan jacques:localize` and translate every reported string in each lang/*.json locale, then `php artisan jacques:localize --check`
- [X] T027 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [X] T028 Run `php artisan test --compact` and the manual walk of quickstart.md at 375, 768 and 1280 px, keyboard only and zoomed to 200%

## Dependencies

- Phase 1 before Phase 2; Phase 2 blocks US3 and the role shown in US1 rows.
- US1 depends on Phase 2. US2 needs US1's view and view model. US3 extends US2's panel.
- US1, US2 and US3 each touch routes/web.php, TeamViewModel.php and its test; work them one story at a time.
- Parallel examples: T002 with T001; T005 with T003; T016 and T017 with T014; T027 with T026.

## Implementation Strategy

MVP is Phases 1 to 4: the list alone has no action left once its row buttons go, so US1 and US2 ship together. US3 ships on its own with the suite green.
