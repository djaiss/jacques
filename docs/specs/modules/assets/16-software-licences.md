# 16. Assets: software licences

| | |
| --- | --- |
| **Identifier** | `modules/assets/16-software-licences` |
| **Status** | Specified |
| **Module** | Assets |
| **Level** | 2, not committed to the first version |
| **Source** | Section 7.1.5 of the monolithic specification |
| **Depends on** | `14-catalogue-and-inventory` |
| **Depended on by** | `19-playbook-integration` |

## 1. Context / Overview

Software needs three levels, not one table.

**The product.** Figma. Adobe Creative Cloud. The thing itself, independent of
any purchase.

**The licence.** A contract the company bought: so many seats, from this date to
that one, renewing or not, at this cost, from this supplier. A company can hold
several licences for the same product, bought at different times on different
terms.

**The seat.** One seat of one licence, attributed to an employee or to an asset.

Collapsing these produces the failure everybody has seen: a spreadsheet where
"Figma" is one row with a number in it, nobody can say who the fifteen seats
belong to, and the renewal is discovered when the card is charged.

Two facts make this worth building rather than buying. The seats attach to
employees who already exist in the core, and revoking them on departure is a
playbook step. Nothing else in the company knows both of those things.

## 2. User Stories & Requirements

### Stories

**As an IT administrator**, I record what software we pay for, how many seats we
bought, and what it costs.

**As an IT administrator**, I attribute a seat to somebody and I can see at a
glance how many are left.

**As an IT administrator**, I attribute a seat to a machine rather than a person,
because some licences are per device.

**As an IT administrator**, I am warned before a licence expires, with enough
notice to renew it.

**As an IT administrator**, I am warned when a licence is nearly fully
attributed, so I can buy more before somebody is blocked.

**As an IT administrator**, when somebody leaves, I get a list of every seat they
hold and revoke them.

**As a finance minded administrator**, I can see what we are spending on software
per year and which licences are barely used.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | A software product belongs to one company and names the software itself. |
| FR-02 | A licence belongs to one product and records the number of seats, the dates, the cost, the supplier and the renewal terms. |
| FR-03 | A product may have several licences. |
| FR-04 | A seat belongs to one licence and is attributed to one employee or one asset, never both. |
| FR-05 | The number of attributed seats may not exceed the number of seats the licence holds. |
| FR-06 | Seat attribution is recorded in an append only table, never updated in place. |
| FR-07 | Revoking a seat closes the attribution and frees the seat. |
| FR-08 | A licence publishes an event as it approaches expiry, and again when it expires. |
| FR-09 | A licence publishes an event when its free seats drop below a threshold. |
| FR-10 | An expired licence stops counting as available and its seats are flagged rather than silently revoked. |
| FR-11 | Every seat held by an employee can be listed from that employee. |
| FR-12 | A licence key, where one exists, is stored encrypted. |

## 3. Technical Specifications & Boundaries

### Data model

```
SoftwareProduct
- id, company_id
- manufacturer_id          nullable, who makes it
- name                     "Figma", "Adobe Creative Cloud"
- category                 nullable
- website_url
- notes

SoftwareLicence
- id, company_id
- software_product_id
- supplier_id              nullable, who we bought it from
- name                     "Figma Organization 2026"
- licence_key              nullable, encrypted
- seats                    how many the contract covers
- minimum_free_seats       threshold for the low seats event
- purchase_date, purchase_cost, order_number
- starts_at, expires_at    nullable
- is_auto_renewing
- notes

SoftwareLicenceSeat
- id, software_licence_id
- assignee_type            employee | asset
- assignee_id
- assigned_by_user_id
- assigned_at
- revoked_at               nullable, null means the seat is held
- notes
```

### Availability

Free seats are derived, never stored:

```
seats - count(seats where revoked_at is null)
```

Same reasoning as the quantitative stock in `15-quantitative-inventory`. A stored
counter that drifts from the attribution rows is worse than no counter.

### Attribution to an employee or an asset

Polymorphic across two types, matching the assignment model in
`14-catalogue-and-inventory`. Some licences are per person, some are per machine,
and the difference is a property of the contract rather than something the
product should decide.

A seat has exactly one assignee. There is no seat attributed to a person and a
machine at once, and no seat held by nobody: an unattributed seat is simply the
absence of a row.

### Expiry

An expired licence does not revoke its seats. It flags them.

Revoking automatically would be the wrong behaviour twice over. It would erase the
record of who held what while the licence was valid, and it would suggest that
the software stopped working, which is a fact about the vendor rather than about
this database.

Two warnings, at thresholds the company sets, defaulting to sixty and thirty days.

### Events published

| Event | When |
| --- | --- |
| `licence.seat_assigned` | A seat is attributed. |
| `licence.seat_revoked` | A seat is released. |
| `licence.seats_low` | Free seats drop below the threshold. |
| `licence.expiring` | A licence enters its warning window. |
| `licence.expired` | A licence passes its expiry date. |

`licence.seats_low` follows the same once per crossing rule as the stock events
in `15-quantitative-inventory`.

### Licence keys

Stored encrypted. A licence key is a credential, and the same reasoning applies
here as to the encrypted custom fields in `17-lifecycle-operations`.

This is also where the constitution test bites. A password vault is named in the
module candidates and the answer there is to integrate rather than rebuild. A
licence key stored alongside the licence it belongs to is not a password vault,
and the line is worth holding: this module stores one key per licence, and does
not grow into a place to keep credentials.

### Out of scope

- **Discovering installed software.** Nothing scans a machine. Everything here is
  recorded by a person. Device compliance and software inventory are IT module
  candidates, see `backlog/23-module-candidates`.
- **Enforcing anything.** Revoking a seat in Jacques does not revoke access at
  the vendor. That would require an integration per vendor, which founding
  principle 6 says comes after the human step, not before.
- **Usage tracking.** Which seats are actually used is a question this data cannot
  answer.
- **Cost allocation to teams or cost centres.** A finance concern, and cost
  centres are explicitly out of scope in `01-company-and-tenancy`.
- **Renewal approval flows.** A purchase request candidate.

## 4. Acceptance Criteria

- [ ] AC-01. A product can be created, and a licence requires a product.
- [ ] AC-02. One product can hold several licences with different dates and seat
      counts.
- [ ] AC-03. Attributing a seat reduces the free count; revoking it restores it.
- [ ] AC-04. Attributing more seats than the licence holds is refused.
- [ ] AC-05. A seat can be attributed to an asset as well as to an employee, and
      never to both at once.
- [ ] AC-06. Revoking a seat closes the row rather than deleting it, and the
      history of who held it survives.
- [ ] AC-07. Free seats dropping below the threshold publishes `licence.seats_low`
      once per crossing.
- [ ] AC-08. A licence entering its warning window publishes `licence.expiring`.
- [ ] AC-09. A licence passing its expiry date publishes `licence.expired` and
      flags its seats without revoking them.
- [ ] AC-10. Asking an employee returns every seat they hold and have held.
- [ ] AC-11. A licence key is unreadable in the database and readable only to
      somebody with `asset.manage`.

## 5. Implementation status

Nothing in this spec exists. It is level 2 and therefore not committed to the
first version.

It depends on `14-catalogue-and-inventory` for manufacturers, and on
`17-lifecycle-operations` for suppliers. Neither is a hard blocker, since both
foreign keys are nullable, but building it before them means the licence has no
supplier to point at.
