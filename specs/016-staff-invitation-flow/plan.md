# Implementation Plan: Staff invitation flow

**Branch**: `2026-10-17-staff-invitation-flow` | **Date**: 2026-10-17 | **Spec**: [spec.md](spec.md)

## Summary

The invitation prepared in step 3 (specs/012) gains a token, dates and five derived states. It is
sent only by an explicit action, from a new onboarding completion screen or a new Team members
screen in Settings, through the existing `SendEmail` job. A public page accepts it, creating an
account from a password or confirming with an existing one of the same company, and lands the
person on the dashboard with a welcome message. Decisions: [research.md](research.md). Tables and
states: [data-model.md](data-model.md). Routes and email: [contracts/routes.md](contracts/routes.md).

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Alpine.js, Alpine Ajax and Tailwind already ship
- **Storage**: SQLite, MySQL, PostgreSQL; five columns on `staff_invitations`, edited in place
- **Testing**: Pest, following `.claude/rules/testing.md`
- **Target Platform**: web back office and one public page, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: Settings team list in one query set with staff, invitation and role eager loaded, about 10 people
- **Constraints**: company isolation on every route, token stored hashed, no email during onboarding, no driver specific SQL, no dashes in prose
- **Scale/Scope**: 1 migration edited, 1 enum, 3 new actions, 3 actions changed, 1 mail, 8 controllers, 3 screens

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Send, revoke and accept are actions; controllers validate and delegate |
| II. Simplicity | Pass. State derives from dates (R2); one send action for three buttons (R3); existing `SendEmail` reused |
| III. Tested | Pass. Every new or changed action, the model, enum, mail, view models and controllers get a test |
| IV. Laravel conventions | Pass. Existing migration edited before production, `up()` only; `RateLimiter`, `lockForUpdate`, `afterCommit` as documented |
| V. Localized and safe | Pass. Revoke and Cancel confirmed; expiry phrase through `trans_choice()` |
| VI. Accessible | Pass, checked by keyboard on desktop, tablet and phone (quickstart) |
| VII. One core | Justified gap: no API nor webhook exists yet (R12) |
| VIII. Short record | Pass. Spec 120 lines, every other artifact under its cap |
| Invariant D3 | Pass. One account per staff record; an account of another company is refused (spec US4.4) |
| Data scoped to company | Pass. Every Settings route 404s for another company; the role is rechecked at send and acceptance |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/016-staff-invitation-flow/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
├── contracts/routes.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
database/migrations/2026_10_15_000002_create_staff_invitations_table.php   five columns (data-model)
database/factories/StaffInvitationFactory.php      states sent, expired, accepted, revoked
config/jacques.php                                 staff_invitation_duration_days = 7
app/Enums/                                         StaffInvitationStatusEnum (new); UserActionEnum, EmailTypeEnum gain cases
app/Models/StaffInvitation.php                     casts, status(), isUsable()
app/Actions/                                       new: SendStaffInvitation, RevokeStaffInvitation, AcceptStaffInvitation
                                                   changed: PrepareStaffInvitation (clears dates, R2),
                                                   UpdateStaffInformation (email change resets, R11)
app/Helpers/StaffInvitationCopy.php                strings shared by the mail and the preview (R9)
app/Mail/StaffInvitationMail.php                   resources/views/mail/staff-invitation.blade.php
app/ViewModels/                                    Invitation/InvitationViewModel, Settings/TeamViewModel,
                                                   Onboarding/CompletionViewModel; StaffPanel::preview() reads the copy
app/Http/Controllers/App/Invitation/               InvitationController, InvitationSignOutController
app/Http/Controllers/App/Settings/Administration/  TeamController, StaffInvitationController,
                                                   StaffInvitationSendingController, StaffInvitationRevocationController
app/Http/Controllers/App/Onboarding/               CompletionController, CompletionInvitationController;
                                                   AvailabilityController redirects to completion (R6)
resources/views/app/invitations/                   show (the R8 variants), error
resources/views/app/settings/administration/team/  index, _row
resources/views/app/onboarding/completion.blade.php
resources/views/app/dashboard.blade.php            shows the welcome flash
routes/web.php                                     routes of contracts/routes.md; Settings index and top bar link Team
lang/*.json                                        through jacques:localize
tests/Unit/{Actions,Enums,Mail,Models,ViewModels}/ mirroring the classes above
tests/Feature/Controllers/App/{Invitation,Settings/Administration,Onboarding}/
```

**Structure Decision**: the existing Laravel layout; nothing new at the top of `app/`.

## Order of Work

1. Migration, config, enum, model, factory states; `PrepareStaffInvitation` and `UpdateStaffInformation` changes.
2. `StaffInvitationCopy`, `StaffInvitationMail`, preview switched to the copy; `SendStaffInvitation` with lock, cooldown and limit (US2).
3. Settings team screen and its four mutation controllers, with confirmations (US2).
4. `AcceptStaffInvitation`, the public page in its R8 variants, sign out, welcome flash (US1, US4).
5. Completion screen and its send controller; availability redirect (US3).
6. `jacques:localize`, Pint, PHPStan, quickstart on three widths.

## Complexity Tracking

No violation to justify.
