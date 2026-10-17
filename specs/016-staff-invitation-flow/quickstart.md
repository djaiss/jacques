# Quickstart: Staff invitation flow

## Prerequisites

`composer run dev` (queue worker included), mail caught locally (`MAIL_MAILER=log` or Mailpit).

## Automated checks

```bash
php artisan test --compact --filter=Invitation
```

```bash
php artisan jacques:localize --check
```

```bash
vendor/bin/pint --test -q && vendor/bin/phpstan analyse --no-progress -q
```

## Manual scenarios

| # | Do | Expect |
| --- | --- | --- |
| 1 | New salon; in step 3 add Sophie Martin with an email, invite as Staff; finish step 4 | No email in the log; completion screen says 1 invitation ready |
| 2 | Press Send invitations | One email with salon, role, 7 days and one button; Settings shows Pending |
| 3 | Open the link signed out, set a password | Lands on the dashboard with "Welcome to" the salon; no onboarding |
| 4 | Open the same link again | "Already used" page |
| 5 | Prepare another member, Send, then Resend | The first link shows "no longer valid", the second works |
| 6 | Revoke a Pending invitation | Confirmation asked; link shows "revoked"; row offers Prepare new invitation |
| 7 | Set `expires_at` in the past with `php artisan tinker` | Row shows Expired; link shows "expired" |
| 8 | Signed in as the owner, open a member's link | Page names the invited address and offers to sign out |
| 9 | Use an email already used by an account of another salon | Refusal page from spec US4.4 |
| 10 | Press Resend 4 times within a day | The fourth is refused with an explanation |

Check scenarios 1, 3 and 6 with the keyboard only, at phone width, and in French.
