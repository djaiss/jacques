# Data Model: Default Roles

## Tables (migrations edited in place, research R7)
| Table | Change |
| --- | --- |
| `companies` | drop `owner_user_id` and its foreign key in the users migration (R3) |
| `roles` | add `description` text, nullable (R6) |
| `user_locations` | new: `user_id`, `location_id`, timestamps, unique on the pair, both foreign keys cascade |

`UserLocation` is the pivot model, as `UserRole` is. `User::locations()` and `Location::users()`
are `belongsToMany` through it.

## Scopes (`ScopeEnum`)

`self` (their own record), `location` (their assigned locations, R2), `company`.

## Permissions (`PermissionEnum`)

S = self, L = location, C = company. Groups: `PermissionGroupEnum` is replaced by Administration,
Locations, Team, Services, Clients, Appointments, Reports.

| Value | Subject | Scopes | Owner | Manager | Staff | Receptionist |
| --- | --- | --- | --- | --- | --- | --- |
| `billing.manage` | company | C | C | | | |
| `company.manage` | company | C | C | | | |
| `role.manage` | company | C | C | | | |
| `location.manage` | location | L C | C | L | | |
| `schedule.view` | employee | S L C | C | L | S | L |
| `schedule.manage` | employee | S L C | C | L | | |
| `employee.view` | employee | S L C | C | L | S | |
| `employee.create` | location | L C | C | L | | |
| `employee.update` | employee | S L C | C | L | S | |
| `service.view` | location | L C | C | L | L | L |
| `service.manage` | location | L C | C | L | | |
| `client.view` | client | S C | C | C | S | C |
| `client.manage` | client | S C | C | C | | C |
| `appointment.view` | appointment | S L C | C | L | S | L |
| `appointment.manage` | appointment | S L C | C | L | S | L |
| `report.view` | location | L C | C | L | | |

- `CreateEmployee` keeps checking `employee.create` at company scope: no location exists when the
  company creates its first person. A Manager creates people once the team screen passes a location.
- `role.manage` covers giving roles and assigning people to locations.
- Creating or deleting a location needs `location.manage` at company scope; updating, archiving
  and restoring one needs it on that location.
- `employee.view_private` and `employee.update_private` go: nothing ever checked them.

## Default roles (`CreateDefaultRoles`)

| Slug | Name, description (written with `__()`) | `is_editable` |
| --- | --- | --- |
| `owner` | Owner, "Full access, including billing and permissions." | false |
| `manager` | Manager, "Runs the locations they are assigned to." | true |
| `staff` | Staff, "Works their own schedule and appointments." | true |
| `receptionist` | Receptionist, "Books appointments and looks after clients." | true |

## Rules

- At least one active user holds `owner` in every company (R4).
- A user and the locations they are assigned to belong to the same company.
