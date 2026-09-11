# 12. Playbooks

| | |
| --- | --- |
| **Identifier** | `core/12-playbooks` |
| **Status** | Specified |
| **Source** | Sections 3.5 and 4.7 of the monolithic specification |
| **Depends on** | `03-employees`, `04-permissions-and-roles`, `08-employee-lifecycle-status`, `11-occurrences` |
| **Depended on by** | `19-playbook-integration`, and every module that wants to participate in a process |

## 1. Context / Overview

A playbook is a sequence of actions triggered by an event in the life cycle of an
employee: an arrival, a departure, a change of role, a return from leave, a
transfer between teams. Each step can reach into any of the four functional
pillars.

This is the orchestration layer, and it is what the product is actually for. The
positioning in `constitution.md` is that Jacques turns employee operations into
clear, repeatable playbooks. Everything else in the core exists so that playbooks
have something to orchestrate.

A remote onboarding, as the source document describes it:

1. Create the employee record. (Manage)
2. Assign equipment. (Operate)
3. Schedule the first one on one with their manager. (Grow)
4. Publish an arrival announcement. (Communicate)
5. Schedule a coffee with a random colleague. (Grow)
6. Assign Alex the task of shipping the laptop. (Operate, no integration needed)

Step 6 is the one to pay attention to. It is a human task assigned to a person,
with no integration behind it, and it is a complete and valid step. Founding
principle 6 in `constitution.md` requires that a playbook works with no external
integration at all.

**This is the riskiest piece to build.** Triggers, conditional steps,
assignments, deadlines, statuses. The source document says so plainly, and the
answer is to stay deliberately simple in the first version: sequential steps with
manual assignment, no complex conditional branching.

## 2. User Stories & Requirements

### Stories

**As a People administrator**, I start from a template for remote onboarding
rather than writing one from nothing.

**As a People administrator**, I change a template: add a step, remove one,
reorder them, change who each is assigned to.

**As a People administrator**, I say which event starts a playbook, and I turn
that off without deleting anything.

**As a People administrator**, I watch a running playbook and see which steps are
done, which are waiting, and which are late.

**As somebody assigned a step**, I see what I have to do, for whom, by when, and
I mark it done.

**As a People administrator**, I run a playbook by hand for somebody, without
waiting for an event.

**As an administrator**, a playbook cannot be used to make somebody do something
they are not allowed to do.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | A playbook template belongs to one company and holds an ordered list of steps. |
| FR-02 | A step has a title, a description, an assignment rule and a due date rule. |
| FR-03 | A step is assigned to a specific employee, or to a relationship resolved when the playbook runs (the manager of the subject, the subject themselves, the holder of a named role). |
| FR-04 | A due date is expressed relative to the start of the run, or to the date the playbook is about. |
| FR-05 | Steps are sequential. There is no conditional branching in the first version. |
| FR-06 | A template is started by a trigger tying it to an event type, or by hand. |
| FR-07 | Starting a playbook creates a run, which snapshots the steps of the template at that moment. |
| FR-08 | Editing a template never changes a run already in progress. |
| FR-09 | A run is about a subject, normally an employee. |
| FR-10 | Each step of a run has a status: pending, done, skipped, cancelled. |
| FR-11 | A run is complete when every step is done, skipped or cancelled. |
| FR-12 | A run can be cancelled, which cancels its remaining steps. |
| FR-13 | Somebody assigned a step is notified, and reminded when it is overdue. |
| FR-14 | A step whose action touches the product is executed under an execution mode, recorded on the template. In the first version the mode is `system`. |
| FR-15 | A playbook may never be used to bypass permissions. The person who wrote the template, the mode a step executes under, and the person a human task is assigned to are three separate things. |
| FR-16 | Every company gets a set of default templates it can edit or delete. |

## 3. Technical Specifications & Boundaries

### Data model

```
PlaybookTemplate
- id, company_id
- name
- description
- created_by_user_id
- execution_mode           system | triggering_user | designated_role
- is_active
- created_at, updated_at

PlaybookTemplateStep
- id, playbook_template_id
- position                 ordering
- title, description
- assignee_rule            employee:{id} | subject | subject_manager | role:{slug}
- due_rule                 relative offset in days from the run or the subject date
- action_type              nullable, when the step does something in the product

PlaybookRun
- id, company_id
- playbook_template_id     the template it came from
- subject_type, subject_id polymorphic, normally an employee
- triggered_by_event_id    nullable, the Occurrence that started it
- started_by_user_id       nullable, set when started by hand
- status                   running | completed | cancelled
- started_at, completed_at

PlaybookRunStep
- id, playbook_run_id
- position
- title, description       snapshotted from the template
- assignee_employee_id     resolved at run time
- due_at                   resolved at run time
- status                   pending | done | skipped | cancelled
- completed_by_user_id, completed_at
```

`PlaybookTrigger`, which ties an event type to a template, is specified in
`11-occurrences` rather than here, because it belongs to the event
infrastructure.

### Snapshotting

A run copies the steps of its template at the moment it starts. Editing the
template afterwards does not touch runs in progress.

This matters more than it sounds. Somebody who reorganises the onboarding
template in March must not silently change what an onboarding started in February
is asking people to do. The cost is duplicated rows; the alternative is a run
whose meaning changes underneath the people carrying it out.

### Assignment resolution

The assignee rule is resolved once, when the run starts.

| Rule | Resolves to |
| --- | --- |
| `employee:{id}` | That employee. |
| `subject` | The employee the run is about. |
| `subject_manager` | The active primary manager of the subject, from `09-managers`. |
| `role:{slug}` | The employees linked to users holding that role. |

If a rule resolves to nobody, the step is created unassigned and flagged, rather
than skipped. An onboarding step nobody owns is a problem to surface, not to hide.

`subject_manager` is why this spec depends on `09-managers`. Without it, the most
common assignment rule in a real onboarding cannot be expressed.

### Playbooks and permissions

Section 4.7 of the source document states the rule plainly: the playbook engine
must not become a way around permissions. Three things are kept distinct.

**Who wrote the template.** Recorded as `created_by_user_id`. Carries no
authority at run time.

**The mode a step executes under.** `execution_mode`, one of `system`,
`triggering_user`, `designated_role`. In the first version it is always `system`.
The column exists now so that introducing the finer modes later needs no
migration.

**Who a human task is assigned to.** A person, who does the thing themselves,
under their own permissions.

A step that assigns Alex the task of shipping a laptop grants Alex nothing. It
tells Alex to do something. That distinction is what makes founding principle 6
safe as well as pragmatic.

### Sequential, not conditional

Steps run in order and all of them are created at the start of the run. There is
no branching, no step that depends on the outcome of another, and no loop.

The temptation to add branching will arrive early, since a template that differs
for contractors is an obvious need. The first version answers it with two
templates and two triggers, each conditioned on the employment type through the
trigger conditions in `11-occurrences`. That covers most real cases without a
workflow engine.

### Default templates

Every company gets templates it can edit or delete, matching the list in the
source document:

- Remote onboarding
- Offboarding
- Contractor onboarding
- Return from parental leave
- Team transfer

They exist so that a company sees what a playbook is by reading one, rather than
by facing an empty screen and a create button.

### Events published

| Event | When |
| --- | --- |
| `playbook.started` | A run begins. |
| `playbook.completed` | Every step of a run is resolved. |
| `playbook.cancelled` | A run is cancelled. |
| `playbook_step.completed` | A step is marked done. |
| `playbook_step.overdue` | A step passes its due date while pending. |

Publishing these means a playbook can trigger a playbook. There is no loop
protection in the first version, which is a known gap rather than a decision, and
the reason default templates never trigger on playbook events.

### Out of scope for the first version

- **Conditional branching.** Stated above.
- **Parallel steps and dependencies between steps.**
- **Steps that call an external service.** Founding principle 6 means integration
  driven steps come after human ones, not before.
- **A template editor with drag and drop reordering.** Reordering exists; the
  interface for it is not specified here.
- **Approval steps.** A step is done or not done. There is no step that somebody
  else has to approve.
- **Loop protection between playbooks.**
- **Playbooks about anything other than an employee.** The subject is
  polymorphic in the schema, so an asset or a team could be a subject later.
  Nothing in the first version does that.

## 4. Acceptance Criteria

- [ ] AC-01. A template can be created with a name and an ordered list of steps.
- [ ] AC-02. Steps can be added, removed and reordered.
- [ ] AC-03. Every new company has the five default templates.
- [ ] AC-04. A trigger on `employee.arrived` starts the matching template when an
      employee becomes active.
- [ ] AC-05. A template can be started by hand for a chosen subject.
- [ ] AC-06. Starting a run copies the steps of the template at that moment.
- [ ] AC-07. Editing a template after a run has started does not change that run.
- [ ] AC-08. `subject` resolves to the employee the run is about;
      `subject_manager` resolves to their active primary manager.
- [ ] AC-09. An assignment rule resolving to nobody creates an unassigned,
      flagged step rather than skipping it.
- [ ] AC-10. A due rule of five days produces a due date five days after the
      relevant date.
- [ ] AC-11. Marking every step done completes the run and records when.
- [ ] AC-12. Cancelling a run cancels its pending steps and leaves the completed
      ones alone.
- [ ] AC-13. Somebody assigned a step is notified, and reminded once it is
      overdue.
- [ ] AC-14. A step assigned to somebody grants them no permission they did not
      already hold.
- [ ] AC-15. A run started by a trigger records the event that started it.
- [ ] AC-16. Starting a run publishes `playbook.started`; completing it publishes
      `playbook.completed`.

## 5. Implementation status

Nothing in this spec exists.

It is the most dependent spec in the core. Before it can be built:

- `11-occurrences` has to exist, or there is nothing to trigger on.
- `08-employee-lifecycle-status` has to exist, or there are no life cycle events
  worth triggering on.
- `09-managers` has to exist, or `subject_manager` cannot be resolved.

It is also the piece the source document identifies as the riskiest. The
mitigation is written into the requirements above: sequential steps, manual
assignment, `system` execution mode, no branching, and a template editor that
does the minimum.

### Suggested build order

1. Templates and steps, with a screen to edit them. No running yet.
2. Runs started by hand, with snapshotting and assignment resolution.
3. Runs started by triggers, once `11-occurrences` is in place.
4. Notifications and overdue reminders.
5. Default templates, added to existing companies through a migration.
