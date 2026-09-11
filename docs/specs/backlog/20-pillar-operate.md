# 20. Pillar: Operate

| | |
| --- | --- |
| **Identifier** | `backlog/20-pillar-operate` |
| **Status** | Placeholder |
| **Source** | Section 3.2 of the monolithic specification |
| **Depends on** | `03-employees`, `10-employment-types`, `13-module-system` |

## Why this is a placeholder and not a spec

The source document gives this pillar three bullet points and no data model.
Writing it out in the shape of the other specs would mean inventing requirements
and presenting them as agreed decisions.

It stays a placeholder until it gets the treatment section 7.1 gave the asset
module: entities, life cycle, events, levels. When that happens it becomes a
folder under `modules/` like `assets/`, not a file here.

## What the pillar is for

The recurring administrative irritants a small company does not want to handle by
hand. This is the least differentiated of the four pillars and the most expected:
nobody chooses Jacques for its leave tracker, and everybody notices its
absence.

## What it contains

### Time tracking

Recording hours worked. Named in the source document with no further detail.

Open questions that have to be answered before this can be specified:

- Is this for billing clients, for compliance with working time rules, or for
  the company to understand where effort goes? The three produce different
  products and only one of them is worth building here.
- Does it compete with tools that are already excellent, which the constitution
  says means integrating rather than rebuilding?

Of everything in the four pillars, this is the item where the constitution test
in `constitution.md` most likely comes back negative.

### PTO, leave and absence

Leave requests, approvals, balances and calendars.

This one clearly belongs. It is the most commonly asked for feature in this
category, it has a real link to entities already in the core, and the parts that
make it hard are parts the core already carries.

What already exists to build on:

- `08-employee-lifecycle-status` has an `on_leave` status, which says somebody is
  away. It deliberately does not track how much leave they have; that is this
  module.
- `10-employment-types` is what eligibility rules would be written against. The
  source document names this explicitly as a reason employment type is a
  structured list rather than free text.
- `09-managers` is what approval routes along.
- `12-playbooks` is what a return from leave triggers.

Open questions:

- Accrual rules vary by country and by company, and are the part that makes leave
  tracking genuinely hard. How much of that variety is in scope?
- Public holidays per office. `05-locations` records a time zone per office and
  deliberately not a calendar.
- Half days, carry over, expiry, unpaid leave, sick leave as a separate balance.

### Hardware and software access

Already specified. This became the Assets module, `modules/assets/`, and is the
one part of this pillar that has been treated in full.

## Next step

PTO is the item to specify first, because it has the clearest link to the core
and the most existing scaffolding waiting for it. Time tracking should be
assessed against the constitution test before any specification effort goes into
it.
