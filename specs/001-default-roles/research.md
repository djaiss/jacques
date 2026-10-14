# Research: Default Roles

## R1. Keep the role tables

- **Decision**: keep `roles`, `role_permissions` and `user_roles`, the role editor and the
  `PendingPermissionCheck` engine. Replace the content of `PermissionEnum` and of
  `CreateDefaultRoles`.
- **Rationale**: D4 now keeps the editor, and the engine already handles cumulative grants with a
  scope.
- **Alternatives considered**: a hardcoded enum of four roles, rejected because the editor stays.

## R2. A third scope: assigned locations

- **Decision**: `ScopeEnum` gains `Location` (`location`) between `Self` and `Company`. A grant at
  `Location` covers a location the user is assigned to, and an employee whose user shares one of
  those locations.
- **Rationale**: this is the only way to express "Assigned locations" from the spec's FR-004 matrix
  in the editor.
- **Alternatives considered**: one role per location, rejected because the number of roles would
  grow with every location.

## R3. Owner is a locked role, `owner_user_id` goes

- **Decision**: drop `companies.owner_user_id` and the owner bypass in `PendingPermissionCheck` and
  `EmployeeScope`. The Owner role (slug `owner`, `is_editable = false`) grants every permission at
  company scope. `User::isOwner()` replaces the column in `DestroyCompany`, `DestroyUser` and
  `UpdateUserInformation`.
- **Rationale**: the spec lets several people hold Owner, so one column cannot hold "the" owner.
- **Alternatives considered**: keeping the column next to the role, rejected because it would
  create two sources of truth.

## R4. At least one active Owner

- **Decision**: `RemoveRole` refuses to take Owner from the last active holder, and `DestroyUser`
  refuses to delete them. No deactivation action exists yet; it inherits the rule when it arrives.
- **Rationale**: this guarantees SC-003 at the only two places that can break it today.

## R5. Permissions without a subject yet

- **Decision**: declare the service, client, appointment, schedule and report permissions in the
  enum and grant them in the default roles. Add no check method for them; the feature that creates
  each subject adds its own check (e.g. `forAppointment()`).
- **Rationale**: this satisfies FR-010 without building screens the product does not have.

## R6. Role names and descriptions

- **Decision**: add a nullable `description` column to `roles`. `CreateDefaultRoles` writes the
  four names and descriptions with `__()`, in the locale of the user who signs up. From then on,
  they are company content the Owner may edit.
- **Rationale**: editable names cannot be translation keys. The salon reads them in its own
  language, which is the language of the reader in nearly every case.
- **Alternatives considered**: translating at display time by slug, rejected because a renamed role
  would show its old name.

## R7. No data conversion

- **Decision**: migrations are edited in place (migrations skill: not in production yet).
  The spec's FR-011 conversion is met by `php artisan migrate:fresh --seed`.
- **Rationale**: no production data exists (spec assumption).
