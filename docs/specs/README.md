# Jacques specifications

This folder holds the Jacques product specification, split into modular files
following the [Spec-Kit](https://github.com/github/spec-kit) methodology. It replaces
the single monolithic specification document that preceded it.

Each spec is self contained: it can be read, reviewed and implemented without
opening the others. Cross references are explicit and one directional wherever
possible, so a spec never silently depends on a decision written somewhere else.

## Conventions

Every spec file follows the same four sections.

1. **Context / Overview**. Why this exists, what problem it solves, where it sits
   in the product.
2. **User Stories & Requirements**. What people need to do, written as stories
   with numbered functional requirements.
3. **Technical Specifications & Boundaries**. Data model, invariants, events
   published, and an explicit list of what is out of scope.
4. **Acceptance Criteria**. Checkable statements. A spec is done when they all
   hold.

Each file opens with a short metadata block: identifier, status, the sections of
the source document it derives from, and its dependencies.

### Status vocabulary

| Status | Meaning |
| --- | --- |
| `Implemented` | Shipped and covered by tests. |
| `Partially implemented` | Some of it exists in the codebase, the rest does not. |
| `Specified` | Written and agreed, not built. |
| `Draft` | Written, awaiting review. |
| `Placeholder` | Named and scoped, not yet specified in detail. |

Anything already in the codebase is called out inside the relevant spec, under an
**Implementation status** heading, so the same work is never described twice as if
it still had to be done.

## Tree

```
docs/specs/
├── README.md                              this file
├── constitution.md                        pitch, target market, founding principles, non goals
│
├── core/                                  always active, never disabled, present in every install
│   ├── 01-company-and-tenancy.md          the company as root object, its fields, tenancy rules
│   ├── 02-users-and-authentication.md     the login account, sessions, two factor, API keys
│   ├── 03-employees.md                    the employee record, its fields, sensitivity categories
│   ├── 04-permissions-and-roles.md        roles, permissions, scopes, exceptions, audit
│   ├── 05-locations.md                    company offices and the employee to office history
│   ├── 06-teams.md                        self referencing teams, primary and transverse membership
│   ├── 07-job-titles.md                   job families, job titles, custom titles, career history
│   ├── 08-employee-lifecycle-status.md    the five system statuses, custom statuses, transitions
│   ├── 09-managers.md                     the reporting relationship and its history
│   ├── 10-employment-types.md             full time, part time, contractor, intern, and custom
│   ├── 11-occurrences.md                  the event catalogue, the persisted log, the publisher
│   ├── 12-playbooks.md                    triggers, steps, assignments, the V1 execution engine
│   └── 13-module-system.md                enabling and disabling modules per company
│
├── modules/                               optional, activated per company
│   └── assets/                            the Assets module
│       ├── 14-catalogue-and-inventory.md  catalogue, storage locations, serialised assets, checkout and checkin
│       ├── 15-quantitative-inventory.md   accessories, consumables, components, stock thresholds
│       ├── 16-software-licences.md        software products, licences, seats
│       ├── 17-lifecycle-operations.md     suppliers, maintenance, audits, attachments, custom fields
│       ├── 18-self-service.md             requests, reservations, acceptance, depreciation
│       └── 19-playbook-integration.md     the onboarding and offboarding hooks
│
└── backlog/                               named and scoped, not specified in detail yet
    ├── 20-pillar-operate.md               time tracking, PTO
    ├── 21-pillar-communicate.md           company news, get to know your colleagues
    ├── 22-pillar-grow.md                  one on ones, rate your manager, skills, e-coffees
    └── 23-module-candidates.md            the unprioritised module list, kept for reference
```

Each module owns a folder under `modules/`. A module is one product area that a
company turns on or off as a whole, and every spec describing part of it lives in
that folder. `assets/` is the first. Future modules (PTO, Time, Skills, one on
ones, internal news) each get a folder of their own rather than a file.

Numbering stays continuous across the whole tree, so a spec identifier is unique
no matter which folder it sits in.

### Reading order

`constitution.md` first, then `core/` in numerical order. The core specs are
ordered by dependency: nothing in `01` needs anything from `02`, and so on. The
module specs assume the whole of `core/`.

### Why the split falls where it does

The source document mixed three kinds of writing: product positioning, data model
decisions, and feature scope. The split separates them.

- Positioning went to `constitution.md`. It constrains every other spec but is not
  itself implementable, so it carries no acceptance criteria.
- Each entity whose relationship to the employee changes over time (location,
  team, job title, status, manager, employment type) got its own spec, because
  each one repeats the same append only history pattern and each one is
  independently shippable.
- The asset module was split by the three levels the source document already
  defined, because only level one is committed to the first version.
- The pillars with no detail in the source document (Operate, Communicate, Grow)
  became backlog placeholders rather than invented specifications. Writing them
  out now would present guesses as decisions.
