# 23. Module candidates

| | |
| --- | --- |
| **Identifier** | `backlog/23-module-candidates` |
| **Status** | Placeholder |
| **Source** | Sections 6 and 6.1 of the monolithic specification |
| **Depends on** | `13-module-system` |

## What this is

A list captured for future reference. Not prioritised, not agreed, not committed.

Every entry is assessed individually against the same test as everything else in
the product, from `constitution.md`:

1. Does this compete head on with a tool that is already excellent? If so,
   integrating beats rebuilding.
2. Does this have a real link to the Employee, Team or Playbook in the core that
   justifies building it in house?

An entry appearing on this list means somebody wrote it down, nothing more.

## Reclassified from permanently excluded

Section 6 of the source document reversed several exclusions when the modular
architecture was adopted. Recorded here so the reversal is not lost.

| Item | Was | Now |
| --- | --- | --- |
| Recruiting (ATS) | Excluded permanently | Future module. `03-employees` and `08-employee-lifecycle-status` already reserve the `candidate` status as the bridge. |
| Project management | Excluded permanently | Future module. |
| Knowledge base and wikis | Excluded permanently | Future module. |
| OKRs | Excluded permanently | Future module, probably merged with performance reviews. |
| Expenses | "Later" | Future module. `01-company-and-tenancy` records a currency on the company partly for this. |
| Discipline cases | Excluded permanently | To reassess. Legally sensitive, and `03-employees` reserves a `relations` category for it with no fields. Not a priority. |
| Work logs and recent ships | Excluded permanently | **Still excluded, even as a module.** Covered by GitHub, Linear and Slack, with no added value identified. |

The last row is the only permanent exclusion left in the product.

## Modules named in the architecture

From the tree in section 6, beyond the pillars and the asset module:

Recognition, Surveys, Performance reviews, Travel, and Community, the last one
breaking into groups, a news feed, discussions, reactions and announcements.

Community carries the Workplace warning recorded in `21-pillar-communicate` and
`13-module-system`. It is the entry on this page most likely to absorb effort for
the least return.

## Candidates captured for reference

### IT and security

Device compliance, software inventory, access reviews, password vault or an
integration, badge and physical access, visitor management.

Password vault fails the first test outright. 1Password exists and is excellent.
`16-software-licences` already draws the line: one licence key stored with its
licence is not a vault, and this module must not grow into one.

Software inventory partially overlaps `16-software-licences`, which records what
was bought rather than what is installed. Discovering what is installed needs
agents on machines, which is a different product.

### Finance and administration

Team budgets, purchase requests, vendor management, company SaaS subscriptions,
corporate cards, invoice approval.

Purchase requests, vendor management and corporate cards all touch categories
where mature specialised tools exist. The source document flags exactly these as
the strongest candidates for integrating rather than rebuilding.

Company SaaS subscriptions overlap `16-software-licences` significantly and might
be the same module rather than a new one.

### Facilities

Desk booking, meeting rooms, parking, office maintenance, mail and packages.

These have the clearest link to something already in the core: `05-locations`
already models offices. Desk booking is the entry on this page with the shortest
path from an existing entity.

### Legal and compliance

Policy acknowledgements, NDA and contract signatures, compliance training,
certification tracking, an audit centre.

Policy acknowledgements are structurally the same feature as asset acceptance in
`18-self-service`: present terms, record who accepted what wording and when. If
both are built, the snapshot mechanism should be shared rather than written
twice.

### Learning

Courses, internal training, certifications, career paths, mentoring.

Career paths are the natural extension of `07-job-titles`, which deliberately
stopped short of levels and competency matrices and said they could be layered on
later without breaking the model.

### Employee experience

Birthdays and anniversaries, kudos and recognition, polls, team events, interest
groups, coffee roulette.

Coffee roulette is already named as e-coffees in `22-pillar-grow` and as a step
in the onboarding example in `12-playbooks`. It is on this list twice, which
suggests it is more wanted than its size implies.

Birthdays and anniversaries need only `03-employees`, which already records a
date of birth and a hire date. It is the cheapest item on this page.

### Health and wellbeing

Wellness challenges, benefits enrolment, employee assistance resources, ergonomic
assessments.

Benefits enrolment is the one entry on this page that contradicts the positioning
in `constitution.md`. Benefits are named there as HR ground that Jacques does
not cover. Building it would mean revisiting the positioning, not just the
roadmap.

## How to use this page

When something here becomes real, it leaves this page and becomes a folder under
`modules/`, the way `assets/` did. Until then, this is a record of what has been
considered, so that the same idea is not re argued from scratch every few months.
