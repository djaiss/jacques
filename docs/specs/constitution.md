# Constitution

| | |
| --- | --- |
| **Identifier** | `constitution` |
| **Status** | Agreed |
| **Source** | Section 1 of the monolithic specification |
| **Depends on** | Nothing |
| **Depended on by** | Every spec |

This document is not implementable and carries no acceptance criteria. It exists
to settle the arguments that every other spec would otherwise reopen. When a spec
and this document disagree, this document wins, and the spec is wrong.

## 1. What Jacques is

Jacques gives small companies one place to manage employees and automate the
processes around them. It turns employee operations into clear, repeatable
playbooks.

Put another way: Jacques is the operating system of your company. Everything
about the employee life cycle, internal operations, and the processes that tie
them together goes through Jacques.

That analogy is not decoration. It maps onto the architecture. The operating
system is the core (employees, teams, permissions, playbooks) and the modules are
the applications that run on it. It is the same analogy that justifies the long
term ambition of being the Odoo of employee management.

## 2. Who it is for

Technology companies, agencies, studios and services businesses of 20 to 300
employees, often distributed or remote first, with a small People or Operations
team.

Two facts define them. They have too much complexity to keep running informally.
They have too few resources to deploy an enterprise HR suite such as Workday.

## 3. What we are actually competing with

Not the large HR platforms. The fragmented stack these companies already cobble
together:

- BambooHR or Personio for employee records
- Greenhouse or Lever for hiring
- Notion for policies
- Jira, Asana or Linear for tasks
- Slack for reminders
- Spreadsheets for the gaps
- Zapier or Make to wire it all together

The product has to be judged against that stack, not against a feature list.

## 4. Why "employee operations" and not "HR"

HR means payroll, compliance, administrative files, insurance and benefits.
Jacques does not cover that ground and should not be positioned as if it did.

Jacques is the daily orchestration between People, managers, IT and
Operations.

## 5. Founding principles

These constrain product and engineering decisions, not just marketing.

1. **Public pricing.** No "call us for pricing".
2. **Immediate trial.** No mandatory sales call before somebody can use the
   product.
3. **Full data export.** Everything a company puts in, it can take out.
4. **Usable without training.** If a screen needs a tutorial, the screen is
   wrong.
5. **A clear self hosted and open source story.**
6. **Human actions before complex automations.** A playbook has to work with no
   external integration at all. Assigning Alex the task of shipping a laptop is a
   complete, valid step. The product must never depend on a catalogue of
   integrations before it delivers value.

Principle 6 is the one most likely to be violated by accident. Any spec that
makes an integration a prerequisite for a feature working is violating it.

## 6. What Jacques is not

Jacques does not try to replace tools that are already excellent on their own
ground. It is not an applicant tracking system, not a project management tool,
not a wiki, and not an OKR tool. It orchestrates around those tools rather than
competing with them.

The test to apply to any proposed feature is the one from section 6.1 of the
source document, and it has two parts.

1. Does this compete head on with a tool that is already excellent? If so,
   integrating beats rebuilding.
2. Does this have a real link to the Employee, Team or Playbook in the core that
   justifies building it in house?

A feature that fails the first test and passes the second is still worth
discussing. A feature that fails both is not.

## 7. The relationship between scope and ambition

Nothing is excluded forever. The core stays deliberately thin and everything that
is not essential to it becomes an optional module rather than a piece of the
core. That is how the narrow first version and the long term ambition coexist
without one eating the other. See `core/13-module-system`.

This is not a licence to widen the first version. The modular architecture is a
technical foundation to lay now, not a justification for building more sooner.

## 8. Permanently out of scope

One item stays out even under the modular architecture:

**Work logs and recent ships.** Covered by GitHub, Linear and Slack. No added
value has been identified, not even as a module.

Everything else that was once excluded (recruiting, project management, knowledge
base, OKRs) is reclassified as a future module in `core/13-module-system`.
