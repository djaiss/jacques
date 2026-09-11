# 11. Occurrences

| | |
| --- | --- |
| **Identifier** | `core/11-occurrences` |
| **Status** | Partially implemented |
| **Source** | Section 5 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy` |
| **Depended on by** | `12-playbooks`, and every spec that publishes an event |

## 1. Context / Overview

This is a system of business events that is deliberately separate from the Laravel
event system, and it is needed from the first version rather than later.

The reason is `12-playbooks`. A playbook reacts to something happening. If the
only thing available is in memory dispatch to hardcoded listeners, then a company
cannot configure which playbook reacts to what, events coming from outside the
application (an issue created on GitHub, a ticket closed on Linear) have nowhere
to arrive, and nothing can be debugged after the fact because nothing was kept.

Three layers.

**A catalogue of business events.** Documented types, named `entity.action`, the
same convention as permissions. `employee.arrived`, `employee.departed`,
`asset.assigned`, `issue.created`.

**A persisted log.** Every event is written to the database, not merely dispatched
in memory. That gives debugging, audit, and one place where internal events and
events arriving from integrations sit side by side.

**Configurable triggers.** Subscriptions stored in the database rather than
listeners written in code, tying an event type to a playbook.

## 2. User Stories & Requirements

### Stories

**As somebody configuring Jacques**, I say which playbook runs when somebody
arrives, and I change my mind later without anybody deploying code.

**As somebody debugging**, I read what actually happened and in what order, days
after it happened.

**As somebody integrating another tool**, I feed events from it into Jacques
and they behave exactly like events raised inside the product.

**As a developer**, I publish an event from an action without knowing or caring
what reacts to it.

**As a company**, an action that triggers a playbook is not slowed down or broken
by whatever that playbook does.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | Every business event has a type of the form `entity.action`, drawn from a documented catalogue. |
| FR-02 | Every published event is persisted before anything reacts to it. |
| FR-03 | An event records its source: internal, or a named integration. |
| FR-04 | An event records its subject polymorphically, so it can be about any entity. |
| FR-05 | An event records its actor: a user, the system, or an integration. |
| FR-06 | An event carries a JSON payload, whose shape is versionable per type. |
| FR-07 | An event records when it occurred, separately from when it was written. |
| FR-08 | Business code publishes through one action and never writes to the table directly. |
| FR-09 | Publishing writes the row before anything reacts to it. |
| FR-10 | Once playbooks exist, publishing resolves the active triggers matching the occurrence and queues one job per match. |
| FR-11 | Playbook execution is asynchronous, so a slow or failing playbook never blocks the action that caused it. |
| FR-12 | A trigger ties an event type to a playbook template, within one company, and can be switched off without being deleted. |
| FR-13 | A trigger may carry conditions. In the first version, simple equality against a field of the payload. |
| FR-14 | An event belonging to no company (a system level event) is allowed and matches no company trigger. |

## 3. Technical Specifications & Boundaries

### Data model

```
Occurrence
- id
- company_id               nullable, null for system level events
- type                     "employee.arrived"
- source                   internal | integration:github | ...
- subject_type             polymorphic
- subject_id
- actor_type               user | system | integration
- actor_id                 nullable
- payload                  JSON
- occurred_at              when it happened
- created_at               when it was written

PlaybookTrigger
- id, company_id
- playbook_template_id
- event_type
- conditions               JSON, simple equality in the first version
- is_active                boolean
```

### The single entry point

All business code publishes through one action.

```
new PublishOccurrence(
    type: OccurrenceTypeEnum::EmployeeArrived,
    company: $company,
    subject: $employee,
    actor: $user,
    payload: [...],
)->execute();
```

It writes the `Occurrence` row and that is all it does today. Nothing reads the
table, which is the intended state until playbooks exist.

Once they do, the same action resolves the active `PlaybookTrigger` rows matching
the type and the company, evaluates their conditions, and queues one
`RunPlaybookJob` per match. The order is the part that matters: the row is
written first, so the log stays a record of what happened rather than a record of
what happened to be listened to.

There is deliberately no internal Laravel event in between. An earlier draft had
the action dispatch one, with a single listener resolving the triggers. That
bought nothing: with one publisher and one listener, the event was a hop between
two pieces of code that already know about each other, and it meant shipping a
class nothing subscribed to. If a second consumer ever appears, the seam can be
introduced then, and the log will already contain everything it would have
carried.

The rule that does matter, and that this keeps: nothing else in the codebase
reacts to business events directly. Anybody wanting to react to something writes
a playbook trigger, not a listener.

### Why asynchronous

Playbook execution is queued rather than run inline, for three reasons.

1. The action that caused the event must not wait for a playbook that assigns
   equipment, sends four emails and creates six tasks.
2. A failing playbook must not roll back or break the action that caused it.
3. `12-playbooks` defines execution modes, one of which runs steps as a
   designated user rather than the triggering one. That is not something to do
   inside the request of the triggering user.

The consequence to accept: a playbook does not run instantly, and the interface
must never imply that it did.

### Occurred and created

Two timestamps, because they genuinely differ for events arriving from
integrations. A GitHub webhook delivered four minutes late occurred four minutes
ago and was created now. Triggers evaluate against `occurred_at`; ordering for
debugging uses `created_at`.

### Payload versioning

The payload shape is a contract per event type, and it is versionable. In the
first version that means the shape is documented alongside the catalogue entry
and additive changes only. Breaking a payload shape means introducing a new event
type, not rewriting the old one, because the log already contains events written
against the old shape.

### The catalogue

Every event type published across the specifications, in one place. Each spec
declares its own; this is the aggregate.

| Type | Published by |
| --- | --- |
| `company.created`, `company.updated` | `01-company-and-tenancy` |
| `user.created`, `user.signed_in`, `user.deactivated` | `02-users-and-authentication` |
| `employee.created`, `employee.updated`, `employee.departure_scheduled` | `03-employees` |
| `location.created`, `location.archived`, `location.reopened`, `employee.location_changed` | `05-locations` |
| `team.created`, `team.deleted`, `employee.team_changed`, `employee.joined_team`, `employee.left_team` | `06-teams` |
| `employee.job_title_changed` | `07-job-titles` |
| `employee.status_changed`, `employee.hired`, `employee.arrived`, `employee.left_on_leave`, `employee.returned_from_leave`, `employee.departed` | `08-employee-lifecycle-status` |
| `employee.manager_changed`, `employee.gained_manager`, `employee.lost_manager` | `09-managers` |
| `employee.employment_type_changed` | `10-employment-types` |
| `asset.checked_out`, `asset.checked_in`, `asset.return_overdue`, `asset.reported_lost`, `asset.warranty_expiring`, `asset.warranty_expired`, `asset.end_of_life_approaching`, `asset.end_of_life_reached`, `asset.audit_due` | the asset module |
| `accessory.stock_low`, `consumable.stock_low`, `component.stock_low`, `license.seats_low` | the asset module |

A module declares its own event types. See `13-module-system`.

### Conditions

In the first version, a condition is equality between a field of the payload and
a literal value.

```json
{ "employment_type": "contractor" }
```

A trigger with no conditions matches every event of its type. A trigger whose
condition references a field absent from the payload does not match, rather than
erroring.

A richer condition engine comes later. The point of the source document is that
it can come later without changing the shape of the log, which is why the log
stores a full payload rather than only the fields anybody currently reads.

### Out of scope

- **A richer condition language.** Comparisons, ranges, boolean combinations.
  Later, without changing the log.
- **Retention and pruning of the log.** It grows without bound in the first
  version. A real concern, deliberately not solved yet.
- **Replaying events.** The log makes it possible; nothing implements it.
- **Outbound webhooks.** Events arriving from integrations are specified; events
  leaving to them are not.
- **Ordering guarantees between events.** They are queued and may complete out of
  order.
- **Auditing.** The audit log in `04-permissions-and-roles` is a different thing
  with a different purpose. The two are not merged.

## 4. Acceptance Criteria

- [ ] AC-01. Publishing an event writes a row with its type, subject, actor,
      payload and occurrence time.
- [ ] AC-02. The row is written before any listener runs.
- [x] AC-03. Publishing writes exactly one row for each thing that happened.
- [ ] AC-04. An active trigger matching the type queues one job.
- [ ] AC-05. Two active triggers matching the same event queue two jobs.
- [ ] AC-06. An inactive trigger queues nothing.
- [ ] AC-07. A trigger belonging to another company never matches.
- [ ] AC-08. A trigger with a condition matching the payload fires; one whose
      condition does not match does not.
- [ ] AC-09. A condition referencing a field absent from the payload does not
      match and raises nothing.
- [ ] AC-10. A failing playbook job leaves the event row in place and does not
      affect the action that published it.
- [ ] AC-11. An event with no company matches no company trigger.
- [ ] AC-12. An event submitted by an integration is stored with its source and
      behaves identically to an internal one.
- [ ] AC-13. `occurred_at` and `created_at` differ for an event submitted with a
      past occurrence time.

## 5. Implementation status

The log is built. Nothing reads it, which is the intended state until playbooks
exist.

### Already built

| Element | Where |
| --- | --- |
| `occurrences` table, with the company, type, source, polymorphic subject, actor, payload and both timestamps | `database/migrations/2026_08_03_000001_create_occurrences_table.php` |
| `Occurrence` model and factory | `app/Models/Occurrence.php` |
| `OccurrenceTypeEnum`, the catalogue, with a `module()` method so a module declares its own types | `app/Enums/OccurrenceTypeEnum.php` |
| `OccurrenceActorEnum` | `app/Enums/OccurrenceActorEnum.php` |
| The single entry point | `app/Actions/PublishOccurrence.php` |
| Publishing from the actions that already ship: company, user, employee and location | `app/Actions/` |
| Publishing from the assets module: checked out, checked in, reported lost, return overdue | `app/Actions/CheckoutAsset.php`, `CheckinAsset.php`, `UpdateAsset.php`, `app/Jobs/CheckOverdueAssetReturns.php` |

FR-01 to FR-09 and FR-14 are satisfied. AC-01 to AC-03 and AC-11 to AC-13 pass.

### Not built

| Gap | Requirement |
| --- | --- |
| No `playbook_triggers` table, and nothing resolves triggers | FR-10, FR-12, FR-13, AC-04 to AC-09 |
| No queued playbook execution | FR-11, AC-10 |
| No entry point for an integration to submit an occurrence | The source column accepts one; nothing writes it but a test |

### What exists that should not be confused with it

- `app/Jobs/LogUserAction.php` and `app/Models/Log.php` record what a user did,
  for that user to read on their own settings screen. It is a feature, not
  infrastructure. It has no subject polymorphism, no source, no payload contract
  and nothing subscribes to it.
- `app/Models/EmailSent.php` records emails sent.

Neither is an occurrence log, and neither should be extended into one. The
occurrence log has different readers (playbooks and integrations rather than
people), a different shape, and a different lifetime.

### Suggested build order

1. The `occurrences` table and the publishing action.
   Publish from the actions that already exist (company, user, employee,
   location). At this point nothing consumes them and the log is purely
   observable.
2. The `playbook_triggers` table and `RunPlaybookJob`, resolved from inside the
   publishing action, once `12-playbooks` has something to run.
3. An entry point for integrations to submit events, once there is an
   integration.

Step 1 is worth doing before `12-playbooks`, because it makes every subsequent
spec able to publish its events as it is built, rather than retrofitting them
across a dozen actions later.
