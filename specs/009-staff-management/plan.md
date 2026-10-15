# Implementation Plan: Staff Management

**Branch**: `2026-10-15-staff` | **Date**: 2026-10-15 | **Spec**: [spec.md](spec.md)

## Summary

`Staff` replaces `Employee` and owns the link to an account (`staff.user_id`). Location
assignments, the services performed and the permission scopes move from the account to the staff
record. Weekly working hours and dated absences are added. The work is data, actions and tests.
The only screens that change are the ones that already read the replaced models (profile, avatar,
the people of a location). Decisions: [research.md](research.md). Tables: [data-model.md](data-model.md).

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new
- **Storage**: SQLite, MySQL, PostgreSQL. Existing migrations are edited (R1)
- **Testing**: PHPUnit, following `.claude/rules/testing.md`
- **Target Platform**: web back office
- **Project Type**: Laravel web application
- **Performance Goals**: no new hot path. The overlap check runs one indexed query per save
- **Constraints**: company isolation on every association, timestamps stored in UTC, no SQL specific to one driver
- **Scale/Scope**: 5 tables, about 22 actions, 5 factories, 3 permission renames and 1 new permission

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. One action per operation, listed below |
| II. Simplicity | Pass. No new abstraction. `StaffScope` replaces `EmployeeScope` one to one |
| III. Tested | Pass. A test for every model, action and factory change. Existing tests are migrated |
| IV. Laravel conventions | Pass. Migrations define only `up()`, existing ones are edited per the migrations skill |
| V. Localized | Pass. Renamed permission labels and log messages go through `jacques:localize` |
| VI. Accessible | Pass. The changed screens keep their markup, only their data source changes |
| VII. One core | Justified gap: no API and no webhooks exist yet (R8). The pull request says so |
| VIII. Short record | Pass. spec 120, plan, research, data model and quickstart all under their caps |
| Invariant "a team member is a user account" | Amended first (FR-017), in the same pull request, before the code |

## Project Structure

### Documentation

```text
specs/009-staff-management/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
└── tasks.md            (/speckit-tasks)
```

No `contracts/`: the feature exposes no API, and its actions are internal (R8).

### Source Code

```text
docs/specs/decisions.md                 D3 and "Salon et équipe" amended
.specify/memory/constitution.md         invariant amended, version 1.3.0
database/migrations/                    R2
database/factories/                     Staff, LocationStaff, StaffLocationService,
                                        StaffWorkingHour, StaffAbsence (replacing the Employee,
                                        UserLocation and TeamMemberService factories)
app/Models/                             Staff, LocationStaff, StaffLocationService,
                                        StaffWorkingHour, StaffAbsence; User, Location, LocationService edited
app/Enums/                              PermissionEnum (R5), UserActionEnum (renamed and new cases)
app/Permissions/                        PendingPermissionCheck::forStaff, StaffScope
app/Actions/
  staff                                 CreateStaff, UpdateStaffInformation, UpdateStaffAvatar,
                                        DestroyStaffAvatar, UpdateStaffPosition, ActivateStaff,
                                        DeactivateStaff, DestroyStaff
  location assignment                   AssignLocation, RemoveLocation (now take Staff),
                                        ActivateLocationStaff, DeactivateLocationStaff
  services                              AssignStaffLocationService, RemoveStaffLocationService
  working hours                         CreateStaffWorkingHour, UpdateStaffWorkingHour, DestroyStaffWorkingHour
  absences                              CreateStaffAbsence, UpdateStaffAbsence, DestroyStaffAbsence
  edited callers                        CreateCompany, CreateDefaultRoles, and the
                                        actions calling forLocation through user locations
app/Http/Controllers/App/Settings/      AvatarController ({staff}), LocationPeopleController ({staff})
app/ViewModels/ and resources/views/    Profile, Settings, Roles, Locations, top bar, avatar: read staff
routes/web.php                          {employee} and {user} parameters become {staff}
tests/Unit/{Models,Actions,Permissions,Enums,ViewModels}  tests/Feature/Controllers
```

Deleted: `Employee`, `UserLocation`, `TeamMemberService`, their factories and tests,
`CreateEmployee`, `UpdateEmployeeInformation`, `UpdateEmployeeAvatar`, `DestroyEmployeeAvatar`,
`AssignTeamMemberService`, `RemoveTeamMemberService`, `EmployeeScope`.

**Structure Decision**: everything goes into existing folders. No amendment is needed for new folders.

## Order of Work

1. Amend `decisions.md` and the constitution (FR-017).
2. Migrations, models and factories for the staff, location staff and staff location service tables. Fix `users`.
3. Permissions (R4, R5), then migrate the existing actions, controllers, view models, views and their tests. The suite passes again.
4. New staff actions (position, activation, deletion) and the location assignment pause.
5. Working hours, then absences, each with its model, factory, actions and tests.
6. `php artisan jacques:localize`, Pint, PHPStan, the full suite.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| VII: no API or webhooks | neither exists in the codebase yet | building them is a separate feature (D27) |
