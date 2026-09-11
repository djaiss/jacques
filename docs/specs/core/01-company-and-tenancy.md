# 01. Company and tenancy

| | |
| --- | --- |
| **Identifier** | `core/01-company-and-tenancy` |
| **Status** | Partially implemented |
| **Source** | Sections 2.1, 2.3 and 2.11 of the monolithic specification |
| **Depends on** | Nothing |
| **Depended on by** | Every other spec in `core/` and `modules/` |

## 1. Context / Overview

The company is the root object of Jacques. Creating an Jacques account does
not create a user first and a company afterwards: it creates a company, and the
person who signed up becomes both its first user and its first employee.

Everything else in the product (employees, teams, offices, roles, playbooks,
assets) belongs to exactly one company and is meaningless outside it. The company
is therefore the tenancy boundary as well as a business object. Two things follow
from that, and this spec exists to state both.

1. Every query in the application is scoped to a company. There is no global list
   of employees, roles or offices.
2. The company carries the defaults that the rest of the product falls back to:
   time zone, language, currency. An employee who has not said which time zone
   they work in works in the company one.

The company also carries the commercial facts about the account: which plan it is
on, whether the instance is cloud hosted or self hosted, when the trial ends, and
where invoices are sent. Those live here rather than in a separate billing object
because a company is the only thing that is ever billed.

This spec covers the company record and the rules around it. It does not cover
who is allowed to change it, which is specified in `04-permissions-and-roles`.

## 2. User Stories & Requirements

### Stories

**As somebody starting with Jacques**, I sign up with my name, my email and
the name of my company, and I land in a working company where I am the owner, an
administrator and the first employee, without a setup wizard.

**As an administrator**, I open the company settings and correct the details of
the company (its legal name, its website, the industry it works in, its declared
size) as they change.

**As an administrator**, I set the default time zone, language and currency of
the company once, so that neither I nor my colleagues have to answer the same
question on every screen.

**As an administrator of a self hosted instance**, I run Jacques without a
plan, a trial or a billing email meaning anything, and nothing in the interface
asks me to upgrade.

**As anybody in the company**, I never see data belonging to another company, and
a link to a record of another company behaves as if that record did not exist.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | Registering creates, in one transaction, a company, a user, an employee, the default roles of the company, and the link making the registering user the owner and an administrator of that company. |
| FR-02 | If any step of registration fails, none of it is persisted. A half created company must not be reachable. |
| FR-03 | A company has a name and a slug. The slug is unique across the whole installation and is derived from the name at creation. |
| FR-04 | A company has a default time zone and a default locale, both always set. New companies default to `UTC` and `en`. |
| FR-05 | The optional descriptive fields of a company (legal name, logo, website, industry, size range, founding date, currency, work mode) can each be left empty without blocking anything. |
| FR-06 | The declared size range is descriptive only. It is never used in place of counting the active employees of the company. |
| FR-07 | The work mode (fully remote, hybrid, office based) is descriptive only. It orients the interface and the default playbook templates. It never restricts what can be recorded, in particular it does not prevent a fully remote company from having offices. |
| FR-08 | A company records the user who owns it. The owner is one specific user, not a role held by several people. |
| FR-09 | A company records whether it is a self hosted instance. When it is, plan, trial and billing fields are ignored by the interface. |
| FR-10 | A company records which modules it has enabled. |
| FR-11 | Changing the company requires the `company.manage` permission at company scope. Every change is written to the log of user actions. |
| FR-12 | Deleting a company is a soft delete. The records of the company remain in the database and stop being reachable. |
| FR-13 | Every record belonging to a company is deleted with it. |

## 3. Technical Specifications & Boundaries

### Data model

```
Company
- id
- name                    the name people call the company by
- slug                    unique, url friendly, used for links and subdomains
- legal_name              nullable, the registered name when it differs
- logo_path               nullable
- website_url             nullable
- industry                nullable
- size_range              nullable, enum, declared headcount bracket
- founded_at              nullable, date
- timezone                required, default UTC
- locale                  required, default en
- currency                nullable, three letter code
- work_mode               nullable, enum: fully_remote | hybrid | office_based
- plan                    required, enum
- is_self_hosted          required, boolean
- billing_email           nullable, distinct from the email of the owner
- trial_ends_at           nullable, datetime
- owner_user_id           FK to User
- settings                nullable, JSON
- created_at, updated_at
- deleted_at              nullable, soft delete
```

### Invariants

**Tenancy.** Every table that belongs to a company carries a `company_id` with a
foreign key that cascades on delete. A record is never reachable from a company
other than the one it belongs to. Asking for a record of another company returns
a not found result, never a forbidden one, so that the existence of that record
is not disclosed.

**Single company per user.** A user belongs to exactly one company. There is no
account switcher and no user shared between two companies. Somebody who works
with two companies has two accounts. This is a deliberate simplification and is
revisited only if it becomes a real constraint, not before.

**Ownership.** `owner_user_id` is nullable in the schema for one reason only: the
company row is written before the user row exists, and is updated within the same
transaction. Once registration completes it is always set. The owner is always a
user of the same company.

**Slug.** Unique across the installation, derived from the name at creation, and
not automatically rewritten when the name changes afterwards. A slug that already
exists is disambiguated at creation. Renaming a company therefore does not break
existing links.

**Defaults.** `timezone` and `locale` are never null. Anything downstream that
needs a time zone or a language and finds none on the employee or the user falls
back to the company, and finding none there is a bug, not a case to handle.

### The settings column

`settings` is a JSON column holding configuration that does not deserve columns of
its own, starting with the list of modules the company has enabled
(`13-module-system`). A dedicated `company_modules` table is not created for the
first version, while the logic stays a list of enabled keys. It becomes one as
soon as a module needs per company configuration beyond on and off.

Nothing that is queried across companies goes in `settings`. A value that has to
be filtered, sorted or joined on gets a column.

### Events published

| Event | When |
| --- | --- |
| `company.created` | A company is created, at the end of the registration transaction. |
| `company.updated` | The details of a company change. |

Both are published through the central publisher described in
`11-occurrences`, never through `event()` directly.

### Out of scope

- **Legal entities, business units and cost centres.** A small company must be
  able to create an employee without modelling any of them. Section 2.3 of the
  source document is explicit: complex structure may exist later as an option and
  must never dominate the first experience.
- **A user belonging to several companies.** See the invariant above.
- **The billing implementation.** The company carries `plan`, `trial_ends_at` and
  `billing_email`, which is enough to know what an account is entitled to.
  Payment, invoicing and plan changes are not specified here.
- **Company hierarchies.** No parent company, no group of companies.
- **The head office.** Which office is the main one is a property of the offices
  of the company and is specified in `05-locations`. The source document proposed
  a `primary_location_id` on the company; the implementation chose an
  `is_primary` flag on the office instead, which keeps the foreign key pointing
  in one direction only.

## 4. Acceptance Criteria

- [ ] AC-01. Registering with a name, an email, a password and a company name
      creates a company, a user, an employee, the default roles, and leaves the
      registering user as owner and administrator.
- [ ] AC-02. If employee creation fails during registration, no company, user or
      role remains in the database.
- [ ] AC-03. Two companies registering under the same name both succeed and end
      up with different slugs.
- [ ] AC-04. A newly created company has `UTC` as its time zone and `en` as its
      locale.
- [ ] AC-05. A company can be created with every optional field empty.
- [ ] AC-06. A user with `company.manage` at company scope can change the company
      details. A user without it gets a not found result.
- [ ] AC-07. Changing the company writes an entry to the log of user actions.
- [ ] AC-08. Requesting a record belonging to another company returns a not found
      result, for every route that takes a record identifier.
- [ ] AC-09. Deleting a company soft deletes it and removes its users, employees,
      roles and offices.
- [ ] AC-10. `settings` round trips a list of enabled module keys.
- [ ] AC-11. Creating a company publishes `company.created`; changing it
      publishes `company.updated`.

## 5. Implementation status

### Already built

| Element | Where |
| --- | --- |
| `companies` table with the fields of section 2.11, except `settings` and `deleted_at` | `database/migrations/0000_01_01_000000_create_companies_table.php` |
| `Company` model, with the `owner`, `users`, `roles` and `locations` relations and `isOnTrial()` | `app/Models/Company.php` |
| `SizeRangeEnum`, `WorkModeEnum`, `PlanEnum` | `app/Enums/` |
| Registration creating company, owner, default roles, administrator role and first employee in one transaction | `app/Actions/CreateCompany.php` |
| Slug derivation and disambiguation | `app/Actions/CreateCompany.php` |
| Changing the company details, authorised against `company.manage` and logged | `app/Actions/UpdateCompany.php` |
| Deleting a company | `app/Actions/DestroyCompany.php` |
| Registration screen | `app/Http/Controllers/App/Auth/RegistrationController.php` |
| Tenancy through `company_id` foreign keys cascading on delete | every migration |

FR-01 through FR-09, FR-11 and FR-13 are satisfied. AC-01 through AC-07 are
covered by the existing tests.

### Not built yet

| Gap | Requirement |
| --- | --- |
| No `settings` JSON column on `companies` | FR-10, AC-10 |
| No soft delete on `companies`; `DestroyCompany` deletes the row outright | FR-12, AC-09 |
| No screen to change the company details; `UpdateCompany` exists but no route or controller reaches it | FR-11 |
| No event published on creation or update; the occurrence system does not exist yet | `11-occurrences`, AC-11 |
| Tenancy is enforced case by case in the actions rather than by a shared scope | AC-08 |

### Divergences from the source document to note

- The source document lists `primary_location_id` on the company. The
  implementation put `is_primary` on the office instead. This spec adopts the
  implementation and the reason is recorded under **Out of scope** above.
- The source document lists `deleted_at` on the company. The migration does not
  have it. Treated here as a gap to close, not as a decision reversed.
