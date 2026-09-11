# 22. Pillar: Grow

| | |
| --- | --- |
| **Identifier** | `backlog/22-pillar-grow` |
| **Status** | Placeholder |
| **Source** | Section 3.4 of the monolithic specification |
| **Depends on** | `03-employees`, `07-job-titles`, `09-managers`, `12-playbooks`, `13-module-system` |

## Why this is a placeholder and not a spec

Four bullet points in the source document and no data model. See
`20-pillar-operate` for the reasoning.

This is the one placeholder where that is genuinely uncomfortable, because the
source document calls this pillar the differentiating heart of the product. The
pillar the product is supposed to be judged on has the least written about it.
That is worth fixing before more of the asset module is specified.

## What the pillar is for

The source document is unambiguous: this is what separates Jacques from a cold
HR system. Recurring use, weekly or monthly, by every employee and every manager
rather than by a People team.

That last point is the one that matters commercially. Everything in Manage and
Operate is used by a handful of administrators. This pillar is the only one where
the whole company opens the product on purpose.

## What it contains

### One on ones

Recurring conversations between a manager and a report: scheduling, shared
agenda, notes, follow up items.

The clearest candidate to specify first. It has a direct link to `09-managers`,
it is the step a remote onboarding playbook already contains in the example in
`12-playbooks`, and it is the feature most likely to produce weekly use.

### Rate your manager

Feedback from a report about their manager.

The most sensitive item in the entire specification. Open questions that are not
technical:

- Is it anonymous? Anonymity is nearly impossible for a manager with two reports,
  and promising it falsely is worse than not promising it.
- Who reads the results? A manager reading their own scores and a People team
  reading them are different products.
- What stops it being used punitively, in either direction?

None of that is answerable from the source document, and none of it should be
guessed at.

### Skills

What people know and what they want to learn.

`07-job-titles` was deliberately kept light with this in mind. It records the
career history of an employee (which titles, when) for free, which is what a
skills feature would build on, without anybody having built a career progression
engine. Section 2.6 says so explicitly.

Out of scope there and therefore an open question here: numeric levels,
competency matrices per level, and salary bands. Those are what a full skills
framework usually implies and what was explicitly deferred.

### e-Coffees

Pairing colleagues for an informal conversation, at random or by some rule.

Small, self contained, and the easiest thing in this pillar to build. It also
appears in the onboarding example in `12-playbooks` as a step, which means the
playbook layer expects it to exist.

## What already exists to build on

- `09-managers` is what one on ones and manager feedback both hang off. Nothing
  in this pillar works without it.
- `07-job-titles` gives career history for free.
- `12-playbooks` already names two steps from this pillar in its onboarding
  example: scheduling the first one on one, and scheduling a coffee.
- `03-employees` is what everything attaches to.

## Open questions across the pillar

- Where do notes live and who can read them? A one on one note is between two
  people. `03-employees` reserves a `relations` category for HR notes and gives
  it no fields, deliberately. One on one notes are not that, and the distinction
  needs to be explicit before anything is stored.
- Does any of this survive somebody leaving? `03-employees` keeps employee
  records after departure. Whether private conversation notes should be kept the
  same way is a different question.
- How much of this competes with tools that already exist? Dedicated one on one
  and feedback products exist and some are good. The constitution test applies,
  and the answer here is probably favourable, since none of them know who reports
  to whom without being told.

## Next step

One on ones, specified properly, is the highest value thing in this document that
is not yet specified. It should be written before the asset module goes past
level 1.
