# Implementation Plan: Default Roles

**Branch**: `2026-10-14-default-roles` | **Date**: 2026-10-14 | **Spec**: [spec.md](spec.md)

## Summary

Replace the permission catalog and the four default roles with Owner, Manager, Staff and
Receptionist. Owner becomes a locked role and replaces `companies.owner_user_id`. A new
`location` scope lets a Manager or a Receptionist act only on the locations they are assigned
to. The role editor stays and learns the third scope. Decisions are in [research.md](research.md),
tables and grants in [data-model.md](data-model.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax

**Storage**: SQLite, MySQL and PostgreSQL, migrations edited in place

**Testing**: PHPUnit, Larastan level 5, Pint

**Target Platform**: web back office, desktop and phone

**Project Type**: Laravel web application

**Performance Goals**: one read of the grants and one of the locations per request, as today

**Constraints**: a refusal answers 404, as `PermissionDecision::authorize()` does today

**Scale/Scope**: 16 permissions, 4 default roles, about 25 files touched

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: `AssignLocation` and `RemoveLocation` are new actions; controllers stay thin |
| II. Simplicity | Pass: the engine gains one scope and one method, nothing else |
| III. Tested | Pass: every changed action, enum, model, view model and controller keeps or gains a test |
| IV. Laravel conventions | Pass: `up()` only, no `constrained()`, migrations edited in place (not in production) |
| V. Localized, safe | Pass: new copy through `__()`, `jacques:localize` run; removing a person from a location asks first |
| VI. Accessible | Pass: the scope picker becomes a radio group with a visible legend |
| VII. One core | Justified: no public API exists yet, so there is no API coverage to add |
| VIII. Short record | Pass: all artifacts within their caps |
| Invariants | Pass: D1 and D4 were rewritten before this plan; D22 is kept (no money permission) |

Re-checked after Phase 1: no change.

## Project Structure

### Documentation (this feature)

```text
specs/001-default-roles/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
└── checklists/requirements.md
```

No `contracts/`: the feature exposes no API, and the screens reuse existing routes plus two.

### Source Code (repository root)

```text
app/Enums/                PermissionEnum, PermissionGroupEnum, ScopeEnum (rewritten)
app/Permissions/          PendingPermissionCheck (+ forLocation, location scope), EmployeeScope
app/Models/               Role (+ OWNER, MANAGER, STAFF, RECEPTIONIST, description),
                          User (+ locations, isOwner), Location (+ users), Company (- owner),
                          UserLocation (new)
app/Actions/              CreateDefaultRoles, CreateCompany, AssignRole, RemoveRole,
                          DestroyCompany, DestroyUser, UpdateUserInformation,
                          location actions (location.manage), AssignLocation, RemoveLocation (new)
app/Http/Controllers/App/Settings/Administration/
                          LocationController (permission), LocationPeopleController (new)
app/ViewModels/Settings/  SettingsViewModel, Administration/RolesViewModel,
                          Administration/LocationsViewModel (people per location)
resources/views/app/settings/administration/
                          roles/_permissions (three scopes), locations/_location-people (new)
database/migrations/      companies, users, roles (edited), user_locations (new)
routes/web.php            POST and DELETE settings/administration/locations/{location}/people
lang/*.json               through jacques:localize
tests/                    mirrors every file above
```

**Structure Decision**: existing folders only. Location assignment mirrors role assignment
(`RolePeopleController`, `AssignRole`, `RemoveRole`), so a reader who knows one knows the other.

## Order of work

1. Migrations, enums, models, factories (Owner role state, `UserLocation`).
2. Permission engine: `location` scope, `forLocation()`, owner bypass removed.
3. `CreateDefaultRoles`, `CreateCompany`, owner checks moved to `User::isOwner()`, last Owner rule.
4. Location assignment actions, controller, routes and screen.
5. Role editor with three scopes and the description field.
6. `jacques:localize`, Pint, PHPStan, full suite, `migrate:fresh --seed`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| No API coverage (VII) | The public API does not exist yet | Building it is out of scope (D27 work) |
