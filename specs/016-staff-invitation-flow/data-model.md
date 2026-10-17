# Data Model: Staff invitation flow

Not in production yet, so `2026_10_15_000002_create_staff_invitations_table.php` is edited in
place (migrations skill), not followed by a new migration.

## `staff_invitations` (changed)

| Column | Type | Rule |
| --- | --- | --- |
| `token_hash` | string(64), nullable, unique | sha256 of the token of the latest send, kept once Accepted or Revoked so the link page can name the case; null when Prepared |
| `sent_at` | timestamp, nullable | latest send; null while Prepared |
| `expires_at` | timestamp, nullable | `sent_at` plus `config('jacques.staff_invitation_duration_days')` (7) |
| `accepted_at` | timestamp, nullable | set once; the invitation is never reusable after it |
| `revoked_at` | timestamp, nullable | set by Revoke; cleared by Prepare new invitation |

Existing columns (`staff_id` unique, `role_id`, `author_id`) are unchanged.

## States (`StaffInvitationStatusEnum`, research R2)

```text
Prepared --send--> Pending --accept--> Accepted
   |                 |  \--7 days--> Expired --send--> Pending
 cancel           revoke                 
   v                 v
 (deleted)        Revoked --prepare--> Prepared
Pending --resend--> Pending (new token)      Pending --staff email changed--> Prepared
```

| From | Action | Writes |
| --- | --- | --- |
| none, Revoked | `PrepareStaffInvitation` | role, author; clears every date and `token_hash` |
| Prepared | `DestroyStaffInvitation` (Cancel) | deletes the row |
| Prepared, Pending, Expired | `SendStaffInvitation` | `token_hash`, `sent_at`, `expires_at` |
| Pending | `RevokeStaffInvitation` | `revoked_at` |
| Pending | `AcceptStaffInvitation` | `accepted_at`; `staff.user_id`; role attached |

## Model and enums

- `StaffInvitation`: new casts (`datetime` for the four dates), `status(): StaffInvitationStatusEnum`, `isUsable(): bool` (Pending).
- `StaffInvitationStatusEnum`: `Prepared`, `Pending`, `Accepted`, `Expired`, `Revoked`, each with a translatable `label()`.
- `UserActionEnum`: adds `StaffInvitationSent`, `StaffInvitationRevoked`, `StaffInvitationAccepted`.
- `EmailTypeEnum`: adds `StaffInvitation = 'staff_invitation'`.
- Factory: states `sent()`, `expired()`, `accepted()`, `revoked()`.

## Validation

- The role belongs to the staff's company (existing check, repeated at send and acceptance).
- The password is `required`, `string`, `max:255`, `Password::min(8)`, as in `RegistrationController`, without the confirmation field the spec mockup omits.
- `staff.email` is set, the staff is active and has no account at acceptance.
