# 02. Users and authentication

| | |
| --- | --- |
| **Identifier** | `core/02-users-and-authentication` |
| **Status** | Implemented |
| **Source** | Sections 2.2 and 2.12 of the monolithic specification, plus work already shipped that the source document never covered |
| **Depends on** | `01-company-and-tenancy` |
| **Depended on by** | `03-employees`, `04-permissions-and-roles`, and anything that has an author |

## 1. Context / Overview

A user is a login. An employee is a person who belongs to the company. They are
deliberately two different things, and this separation is the single most
important modelling decision in the product.

An employee can exist with no user. A user can exist without standing for any
employee. That buys five things at once:

1. Employees who have no access to Jacques at all.
2. An employee prepared before their first day, with a record but no account.
3. History kept after somebody leaves, without keeping their account alive.
4. Administrative accounts that do not stand for a person on the payroll.
5. Candidates handled before they become employees.

That last point is the bridge towards a future recruiting module: a candidate
becomes an employee without an applicant tracking system having to be folded back
into the core.

This spec covers the user record, how somebody proves who they are, and what they
can change about their own account. It does not cover what a user is allowed to
do, which is `04-permissions-and-roles`.

## 2. User Stories & Requirements

### Stories

**As somebody invited to Jacques**, I sign in with an email and a password, or
with a link sent to my email, and I do not have to remember which of the two the
company chose.

**As a security conscious user**, I turn on two factor authentication, keep a set
of recovery codes, and regenerate them when I have used them.

**As a user**, I change my own password, and I can see when I last changed it.

**As a user**, I set the language of my interface and whether I want a 24 hour or
a 12 hour clock, independently of what the company chose.

**As a user**, I read the log of the actions I performed and the emails the
product sent me, so nothing happens on my account that I cannot account for.

**As a user integrating Jacques with something else**, I create and revoke API
keys of my own.

**As an administrator**, I suspend somebody without deleting them, and I see when
each user last signed in.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | A user belongs to exactly one company and can optionally point at one employee. |
| FR-02 | The link between user and employee is a nullable foreign key on the user. The employee holds no reference back. An employee exists independently of any access. |
| FR-03 | At most one active user per employee at a time. |
| FR-04 | The email address of a user is the login identifier and is unique across the installation. |
| FR-05 | The password hash is nullable, because a user may authenticate exclusively through single sign on. |
| FR-06 | A user records which SSO provider they sign in with, when applicable. |
| FR-07 | A user can be deactivated without being deleted. A deactivated user cannot sign in. |
| FR-08 | A user has an optional locale that overrides the company one. |
| FR-09 | The date of the last sign in is recorded, for audit and for finding dormant accounts. |
| FR-10 | Deleting a user is a soft delete. |
| FR-11 | Roles are not fields on the user. They are relations, and a user may hold several. |
| FR-12 | A user can enrol in two factor authentication, confirm the enrolment, receive single use recovery codes, regenerate them, and turn it off. |
| FR-13 | Signing in from an unfamiliar address notifies the user. |
| FR-14 | A user can sign in through a single use link sent by email, without a password. |
| FR-15 | Every email sent to a user is recorded and readable by that user. |
| FR-16 | Every action a user performs is logged and readable by that user. |

## 3. Technical Specifications & Boundaries

### Data model

```
User
- id
- company_id                 FK, required
- employee_id                FK, nullable
- email                      unique, the login identifier
- email_verified_at          nullable
- password_hash              nullable, null when SSO only
- password_changed_at        nullable
- sso_provider               nullable
- is_active                  boolean, default true
- locale                     nullable, falls back to the company locale
- time_format                '24' or '12'
- last_login_at              nullable
- last_login_ip              nullable
- two_factor_secret          encrypted, nullable
- two_factor_confirmed_at    nullable
- two_factor_recovery_codes  encrypted, nullable
- remember_token
- created_at, updated_at
- deleted_at                 soft delete
```

### Invariants

**Direction of the link.** The foreign key lives on the user and only on the
user. This is not an arbitrary choice of side. An employee is the durable record
and an account is a temporary grant of access to it. Putting `user_id` on the
employee would suggest the opposite.

**One active user per employee.** Enforced in the application, not by a unique
index, because soft deleted users would otherwise occupy the slot forever.

**Verified enrolment.** `two_factor_secret` being set does not mean two factor is
in use. Only `two_factor_confirmed_at` does. A user who starts enrolling and
abandons halfway is not locked out.

**Fallbacks.** Locale falls back from user to company. Time format is always set
on the user. Neither falls back to a hardcoded value at the point of use.

### Boundaries between this spec and its neighbours

- Who a user is allowed to act on: `04-permissions-and-roles`.
- What an employee record contains: `03-employees`.
- The company defaults a user falls back to: `01-company-and-tenancy`.

### Events published

| Event | When |
| --- | --- |
| `user.created` | An account is created. |
| `user.signed_in` | A user signs in successfully. |
| `user.deactivated` | A user is suspended. |

The action log described below is not a replacement for these. It records what
somebody did for that person to read back. Occurrences exist so that playbooks
can react. See `11-occurrences`.

### Out of scope

- **Multi company accounts.** One user, one company. Stated in
  `01-company-and-tenancy` and repeated here because this is where somebody would
  be tempted to break it.
- **Single sign on itself.** The `sso_provider` column reserves the space. No
  provider is implemented.
- **Passkeys and WebAuthn.**
- **Invitation flows.** Creating a user for a colleague is possible through the
  actions; there is no invitation screen, no acceptance step and no expiry.
- **Session management screens.** A user cannot see or revoke their other
  sessions.

## 4. Acceptance Criteria

- [x] AC-01. A user can be created with no employee attached, and an employee can
      be created with no user attached.
- [x] AC-02. Creating a second active user for an employee who already has one is
      refused.
- [x] AC-03. Signing in with a correct email and password succeeds and records
      the time and the address.
- [x] AC-04. Signing in with a deactivated account is refused.
- [x] AC-05. A user with two factor confirmed is asked for a code after their
      password, and a valid recovery code is accepted in its place exactly once.
- [x] AC-06. Enrolling in two factor without confirming leaves the account
      signing in with a password alone.
- [x] AC-07. Requesting a magic link sends one, and consuming it signs the user
      in and cannot be repeated.
- [x] AC-08. Changing a password records when it changed and shows it on the
      security screen.
- [x] AC-09. Signing in from an address the user has not used before sends them a
      notification.
- [x] AC-10. A user reads their own action log and their own sent emails, and
      neither shows anything belonging to another user.
- [x] AC-11. A user creates an API key, uses it against the API, revokes it, and
      it stops working.
- [x] AC-12. Setting a locale on the user changes the interface language without
      changing it for anybody else in the company.
- [ ] AC-13. Creating a user publishes `user.created`, signing in publishes
      `user.signed_in`, and suspending publishes `user.deactivated`.

## 5. Implementation status

This is the most complete area of the codebase. The source document specified
only the `User` table; everything below it was built beyond that.

### Already built

| Element | Where |
| --- | --- |
| `users` table with every field of section 2.12, plus two factor, time format, last login address and soft deletes | `database/migrations/0001_01_01_000000_create_users_table.php` |
| `User` model with `company`, `employee`, `roles`, `magicLinks`, `logs` and `emailsSent`, and the `usesSingleSignOn()`, `hasConfirmedEmail()` and `usesTwoFactorAuthentication()` helpers | `app/Models/User.php` |
| Registration, login, logout, email verification, password reset | `app/Http/Controllers/App/Auth/` |
| Magic links, with creation and single use consumption | `app/Actions/CreateMagicLink.php`, `app/Actions/ConsumeMagicLink.php`, `app/Models/MagicLink.php` |
| Two factor enrolment, confirmation, challenge, disabling, recovery codes | `app/Actions/EnableTwoFactorAuthentication.php`, `ConfirmTwoFactorAuthentication.php`, `VerifyTwoFactorCode.php`, `DisableTwoFactorAuthentication.php`, `RegenerateTwoFactorRecoveryCodes.php`, `app/Helpers/RecoveryCodes.php` |
| API keys through Sanctum | `app/Actions/CreateApiKey.php`, `app/Actions/DestroyApiKey.php` |
| Preferences: locale and time format | `app/Actions/UpdatePreferences.php`, `app/Enums/TimeFormatEnum.php` |
| The log of user actions and the log of emails sent, each with its own screen | `app/Jobs/LogUserAction.php`, `app/Models/Log.php`, `app/Models/EmailSent.php`, `app/Enums/UserActionEnum.php`, `app/Enums/EmailType.php` |
| Notification on sign in from a new address, and on a failed sign in | `app/Mail/NewLoginDetected.php`, `app/Mail/UserIpChanged.php`, `app/Mail/LoginFailed.php`, `app/Jobs/CheckLastLogin.php` |
| Four interface languages | `lang/en.json`, `fr_FR.json`, `es_ES.json`, `de_DE.json` |

FR-01 through FR-16 are satisfied. AC-01 through AC-12 are covered by tests.

### Not built yet

| Gap | Requirement |
| --- | --- |
| No occurrences published; the event system does not exist | FR none, AC-13 |
| No single sign on provider implemented, only the column | FR-06 |
| No invitation flow for creating a colleague account | Out of scope for now |
| No screen listing or revoking active sessions | Out of scope for now |
