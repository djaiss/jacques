# Research: Service Management

## R1. Who may change what

**Decision**: catalogue actions check `ServiceManage` with `forCompany()`; location offer and team
member actions check `ServiceManage` with `forLocation()`. Reading checks `ServiceView`.

**Rationale**: `forCompany()` passes only for a company scope grant, and the default Manager holds
`ServiceManage` at location scope (`CreateDefaultRoles`). The engine already gives the answer to
clarification 3, so no permission or role changes.

**Alternatives considered**: a new `CatalogueManage` permission, rejected as a duplicate of a scope.

## R2. Steps are written with their service

**Decision**: `CreateService` and `UpdateService` take the full ordered list of steps; an update
replaces them. There are no step actions of their own.

**Rationale**: a service always has at least one step (FR-004), which per step actions could break
between two calls. The rank is the index in the list.

**Alternatives considered**: `CreateServiceStep` and friends, rejected for the reason above.

## R3. Duration is computed, not stored

**Decision**: `Service::duration()` is an accessor summing the steps. No `duration` column.

**Rationale**: a stored copy can disagree with its steps. The appointment snapshot (FR-018) is the
place where a duration gets frozen.

## R4. Positions

**Decision**: a new category goes last in its company, a new service last in its category (services
without a category form one group). `UpdateServiceCategoryPosition` and `UpdateServicePosition`
move one record and shift its siblings in one transaction.

**Alternatives considered**: a fractional or gapped position, rejected as needless for a few dozen rows.

## R5. Deactivation

**Decision**: `services.is_active` and `location_services.is_active`, as `users.is_active` does.
`DeactivateService` and `ActivateService` mirror `ArchiveLocation` and `RestoreLocation`.

**Alternatives considered**: an `archived_at` timestamp as on locations, rejected because the
description asks for a boolean and nothing needs the date.

## R6. Naming the team member association

**Decision**: model `TeamMemberService`, table `team_member_services`, joining a `user_id` to a
`location_service_id`. This departs from `staff_location_services` in the description.

**Rationale**: Staff is the name of a default role, and the association applies to anybody,
Owner included. `TeamMemberService` is also the term in `docs/specs/decisions.md`.

## R7. Keeping associations inside one company

**Decision**: each action compares `company_id` of every record it joins, and throws
`ModelNotFoundException` on a mismatch, as `AssignLocation` does. Assigning a team member also
checks `user_locations`; `RemoveLocation` deletes that person's team member services there.

## R8. Deferred to the appointment feature

Refusing to delete a service with appointments (FR-014) and the booking snapshot (FR-018). Until
then `DestroyService` deletes the service, its steps, its offers and who performs it.
