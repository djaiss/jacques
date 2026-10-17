# Research: Staff invitation flow

## R1. Token: random, hashed, no signed URL

**Decision**: each send draws `Str::random(64)`, stores `hash('sha256', ...)` in `token_hash`, and emails `invitations/{token}`. The route accepts `[A-Za-z0-9]{64}` and is throttled, exactly as `CreateMagicLink` and `ConsumeMagicLink` do.
**Rationale**: the pattern already ships and is tested. Expiry lives in `expires_at`, revocation in `revoked_at`, supersession in the hash being replaced.
**Rejected**: a Laravel signed URL on top. It adds nothing once the hash and `expires_at` are checked, and cannot be revoked.

## R2. State derives from dates, not a column

**Decision**: `StaffInvitation::status()` returns a `StaffInvitationStatusEnum`, in this order: `accepted_at` set is Accepted, `revoked_at` set is Revoked, `sent_at` null is Prepared, `expires_at` past is Expired, otherwise Pending.
**Rationale**: a stored status and its dates could disagree; expiry needs no scheduled job.

## R3. One send action covers Send, Resend and Send new invitation

**Decision**: `SendStaffInvitation` accepts Prepared, Pending and Expired, and refuses Accepted and Revoked. Inside a transaction it locks the invitation row (`lockForUpdate`), rereads its state, writes a new `token_hash`, `sent_at` and `expires_at`, then dispatches the existing `SendEmail` job with `afterCommit()` on the `high` queue.
**Rationale**: the three buttons differ only in label. One action is one test target.
**Rejected**: a dedicated job. `SendEmail` already records the email in `emails_sent`.

## R4. Duplicates and abuse

**Decision**: the row lock serializes two clicks; the second one sees a send younger than 60 seconds and changes nothing. `RateLimiter` keyed by staff id caps sends at 3 per 24 hours (FR-006) and throws a `ValidationException` the controller shows.
**Rationale**: no column is needed for a sliding window, and a retried job reuses the mailable it was given, so it never draws a second token.
**Accepted risk**: a retry after a mail provider timeout may deliver twice; the second copy carries the same valid link.

## R5. No send while the owner onboarding is pending

**Decision**: `SendStaffInvitation` refuses while `company.is_onboarding_pending` is true.
**Rationale**: guarantees SC-003 even from Settings, and an invited user can never meet the onboarding gate.

## R6. Completion screen outside the step gate

**Decision**: completing step 4 redirects to `onboarding/completion` instead of the dashboard. That route is not behind `onboarding.step`, since onboarding is over; it shows the number of Prepared invitations and posts to `onboarding/completion/invitations`, which runs `SendStaffInvitation` for each.

## R7. Acceptance

**Decision**: `AcceptStaffInvitation(token, user, password)` locks the invitation, checks it is Pending, that the staff member is active, still has the invited email and no account, and that `user.email` equals `staff.email`. Then, in one transaction: creates the user through `CreateUser` when none is given (with `email_verified_at` set, since the link proves the address), sets `staff.user_id`, attaches the role with `syncWithoutDetaching`, and sets `accepted_at`. The hash stays, so a second visit reads "already used".
**Rationale**: `AssignRole` needs an author holding `RoleManage`, which the invitee is not, and the author may be gone; the role belongs to the staff's company, checked again here.
**Concurrency**: the lock plus the unique `users.email` let exactly one of two acceptances win; the loser sees "already used".

## R8. The four visitors of the link

| Visitor | Page shows | Submit |
| --- | --- | --- |
| Signed out, no account uses the email | name, email (read only), password | creates the account and accepts, signs in |
| Signed out, an account of this company uses it | sign in prompt; `intended` brings them back | none until signed in |
| Signed in as the invited email | one confirmation button | accepts |
| Signed in as somebody else | the invited address, a sign out button | signs out, back to the same page |
| An account of another company uses it | spec US4.4 refusal | none |

## R9. Email and preview share one copy

**Decision**: `StaffInvitationMail` (markdown, `mail.staff-invitation`) renders the strings of `StaffInvitationCopy`, a small class in `app/Helpers` that both the mail and `StaffPanel::preview()` read. The copy follows the spec: inviter, salon, role, expiry in days, one button "Accept invitation".
**Rationale**: point 12 of the input; the preview of 012 cannot drift from what is sent.

## R10. Landing after acceptance

**Decision**: redirect to `dashboard.index` with a `status` flash "Welcome to :salon!" and the explanatory sentence. The welcome screen and onboarding gates read company flags that are already false (R5).

## R11. Invalidation on staff changes

**Decision**: `UpdateStaffInformation` resets `token_hash`, `sent_at` and `expires_at` when the email changes (back to Prepared); it already deletes the invitation when the email is removed. Deleting the staff cascades. Deactivation is checked at acceptance (R7).

## R12. API and webhooks

**Decision**: justified gap, as in 012 R10: no public API or webhook exists yet.
