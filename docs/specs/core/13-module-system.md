# 13. Module system

| | |
| --- | --- |
| **Identifier** | `core/13-module-system` |
| **Status** | Specified |
| **Source** | Sections 6 and 6.1 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy`, `04-permissions-and-roles`, `11-occurrences` |
| **Depended on by** | Every spec in `modules/` and `backlog/` |

## 1. Context / Overview

The long term ambition is to be the Odoo of employee management: a deliberately
thin core, surrounded by modules a company turns on and off.

That resolves the tension between a narrow first version and a broad ambition.
Nothing is excluded forever. Everything that is not essential to the core becomes
an optional module rather than a piece of the core.

```
Jacques

Core                          always active, never disabled
├── Employees
├── Teams
├── Permissions
└── Playbooks

Modules                       activated per company
├── Assets
├── PTO
├── Time
├── Skills
├── One on ones
├── Recognition
├── Surveys
├── Community
│   ├── Groups
│   ├── News feed
│   ├── Discussions
│   ├── Reactions
│   └── Announcements
├── Recruiting
├── Performance reviews
├── Expenses
├── Travel
├── Project management
├── Knowledge base
└── ...
```

The core is what specs `01` to `12` describe. Everything else is a module,
including the Operate, Communicate and Grow pillars, which should each break into
independent modules rather than remaining monolithic blocks.

**This is a structural technical choice, not a list of features to tick off
later.** It means module activation per company, permissions scoped by module,
playbook triggers declared by module, and migrations and data isolated by module.
Deciding it late costs a rewrite; deciding it now costs discipline.

It is equally not a licence to widen the first version. The modular architecture
is a foundation to lay now, not a reason to build more sooner.

## 2. User Stories & Requirements

### Stories

**As an administrator**, I turn on the modules my company needs and leave the
rest off, and the interface shows me only what I turned on.

**As an administrator**, I turn a module off without losing the data it holds, in
case I turn it back on.

**As an administrator**, the permissions of a module I have not enabled do not
clutter the screen where I configure roles.

**As a developer adding a module**, I declare its permissions, its events, its
navigation and its migrations in one place, and I do not have to edit the core to
register any of them.

**As a company on a self hosted instance**, the set of available modules is the
same as on the cloud.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | The core is always active and cannot be disabled. |
| FR-02 | Every other feature area belongs to exactly one module. |
| FR-03 | A company records which modules it has enabled. |
| FR-04 | Enabling and disabling a module requires `company.manage` at company scope. |
| FR-05 | Disabling a module hides its screens and navigation and stops its triggers firing. It does not delete its data. |
| FR-06 | A module declares its own permissions. Those permissions are only offered when the module is enabled. |
| FR-07 | A permission belonging to a disabled module denies, whatever a role grants. |
| FR-08 | A module declares its own event types, which join the catalogue in `11-occurrences`. |
| FR-09 | A module declares its own playbook step types, so that a playbook can reach into it. |
| FR-10 | A module declares its own navigation entries. |
| FR-11 | A module owns its migrations, and its tables are recognisable as belonging to it. |
| FR-12 | A module may depend on the core. A module may not depend on another module. |
| FR-13 | The set of available modules is the same on cloud and self hosted instances. |

## 3. Technical Specifications & Boundaries

### Where the enabled list lives

In `settings`, the JSON column on the company, as a list of enabled module keys.

No `company_modules` table in the first version, while the logic stays a list of
keys. It becomes one as soon as a module needs per company configuration beyond
on and off, or as soon as something needs to query across companies by module.

That threshold is worth naming precisely, because it will be crossed: the first
module that needs a setting of its own, or the first report answering "how many
companies use PTO", is when the table is created.

### What a module declares

A module is a directory with a manifest. The manifest is the only thing the core
reads, and the core reads nothing else about the module.

```
key                    "assets"
name                   "Assets"
description
permissions            the PermissionEnum cases the module owns
event_types            the event types it publishes
playbook_step_types    the step types it contributes
navigation             the entries it adds and where
```

The rule that makes this worth doing: **adding a module never requires editing
the core.** If registering a module means adding a case to an enum in the core,
or a line to a navigation array in the core, the boundary is not real. That
constraint shapes how permissions are declared, and is the main thing this spec
asks of the codebase as it exists today.

### The tension with the permission enum

`04-permissions-and-roles` makes permissions an enum rather than a table, for
good reasons that spec sets out. FR-06 here wants modules to declare their own
permissions without editing the core.

The two are reconcilable but not for free. The resolution: each module owns its
own enum of permissions, and the core composes them rather than holding one flat
enum. `PermissionEnum` becomes the core enum, and the check resolves a permission
key against the core enum plus the enums of the enabled modules.

This is the single largest refactor the module system implies, and it is worth
doing before the first module ships rather than after. Doing it with one module
in existence is a contained change; doing it with four is not.

### Disabling

Disabling hides and stops. It does not delete.

- Screens and navigation disappear.
- Permissions of the module deny, whatever roles grant. A role keeps the grant,
  and the grant starts working again when the module is re enabled.
- Playbook triggers belonging to the module stop firing.
- Playbook steps of a type belonging to the module, in runs already going, are
  cancelled rather than left pending forever.
- Data stays.

The last point in that list is the one to get right. A run half executed when
somebody turns a module off must not sit waiting on a step nobody can complete.

### No dependencies between modules

FR-12 forbids a module depending on another. A module depends on the core and on
nothing else.

This is a strong constraint and it will be tested. Assets and PTO both want to
know about the employee, which is core and therefore fine. If two modules ever
genuinely need each other, the shared part belongs in the core, or they are one
module.

### Reclassification from the original scope

Section 6 of the source document reversed several exclusions. Recording it here
so the change is not lost.

| Item | Was | Now |
| --- | --- | --- |
| Recruiting (ATS) | Excluded permanently | Future module |
| Project management | Excluded permanently | Future module |
| Knowledge base and wikis | Excluded permanently | Future module |
| OKRs | Excluded permanently | Future module, probably merged with performance reviews |
| Expenses | "Later" | Future module |
| Discipline cases | Excluded permanently | To reassess, legally sensitive, not a priority |
| Work logs and recent ships | Excluded permanently | Still excluded, even as a module |

The first version stays the core plus a minimal subset of modules: Assets, PTO,
Time, Skills, One on ones and internal news.

### A note on Community

Meta shut Workplace, the former Facebook for Work, in 2024 and 2025, having
failed to beat Slack and Teams on their own ground despite millions of users at
its peak.

The lesson taken here: the differentiator is not a standalone social feed, but a
layer connected to data already structured elsewhere in the core. Announcements
generated from arrival events. Groups populated automatically from existing
teams. Not another isolated enterprise social network.

### Out of scope

- **Third party modules.** Modules are written by the product team. There is no
  plugin API, no marketplace and no module written by a customer.
- **Per module billing.** A module is enabled or not; the plan of the company
  does not gate modules in the first version.
- **Runtime installation.** Modules ship with the application. Enabling one turns
  on code that is already deployed.
- **Module versioning.**

## 4. Acceptance Criteria

- [ ] AC-01. The core is active for every company and cannot be disabled.
- [ ] AC-02. Enabling a module records it in the settings of the company.
- [ ] AC-03. Disabling a module hides its screens and navigation entries.
- [ ] AC-04. Disabling a module does not delete any of its data, and re enabling
      it brings the data back unchanged.
- [ ] AC-05. A permission belonging to a disabled module denies, even for a user
      whose role grants it.
- [ ] AC-06. Permissions of a disabled module are not offered on the role
      configuration screen.
- [ ] AC-07. Re enabling a module restores permissions that roles still grant,
      with no reconfiguration.
- [ ] AC-08. A trigger belonging to a disabled module does not fire.
- [ ] AC-09. A pending playbook step belonging to a disabled module is cancelled
      rather than left waiting.
- [ ] AC-10. A user without `company.manage` cannot enable or disable a module.
- [ ] AC-11. Adding a module requires no change to any file in the core.
- [ ] AC-12. The event types of a module appear in the catalogue only while it is
      enabled.

## 5. Implementation status

Nothing in this spec exists. There is no module concept, no `settings` column on
the company, and no manifest.

Two facts about the current codebase matter for it.

**Everything currently built is core.** Company, users, employees, permissions
and locations all belong to specs `01` to `12`. Nothing built so far would have
to move into a module.

**`PermissionEnum` is a single flat enum in the core.** That is correct today,
with no modules. It is the thing FR-06 requires changing, and the change is
described above.

### Suggested build order

1. The `settings` column on the company and the enabled list. Trivial, and
   unblocks the rest.
2. Composing permissions from the core enum plus module enums. This is the real
   work and it should happen with zero or one module in existence.
3. The manifest, navigation registration and enabling screen.
4. The first module, Assets, built against the boundary rather than retrofitted
   into it.

Step 2 before step 4 is the recommendation that matters. Building Assets first
and extracting the boundary afterwards is how a module system stops being real.
