# Data Model: Onboarding Step 3, Your Team

One new table. `staff`, `location_staff` and `staff_location_services` keep their columns (spec 009);
what changes is which values their actions accept (research R4) and who is assigned when (R2).

## staff_invitations (new migration `2026_10_15_000002`)

| Column | Type | Rule |
| --- | --- | --- |
| id | bigint | primary key |
| staff_id | bigint, unique | staff member invited; cascade on delete |
| role_id | bigint | role the account will hold; cascade on delete |
| author_id | bigint, nullable | user who prepared it; null on delete |
| timestamps | | `created_at` is when it was prepared |

No email column: the address is `staff.email` (R1). The sending feature adds its own columns.

## Rules carried by the actions

| Rule | Where |
| --- | --- |
| a staff member with no email has no invitation | `PrepareStaffInvitation` refuses; `UpdateStaffInformation` deletes it when the email is emptied |
| the role and the staff member belong to the author's company | `PrepareStaffInvitation` (`ModelNotFoundException`) |
| at most one invitation per staff member; preparing again changes the role | `PrepareStaffInvitation` |
| a staff member already linked to an account is not invited | `PrepareStaffInvitation` refuses |
| an email is unique among the staff of a company, lower case | `CreateStaff`, `UpdateStaffInformation` |
| a synced service is offered at the location, and the staff member is assigned there | `SyncStaffLocationServices`, as `AssignStaffLocationService` |

## Derived values

| Value | Definition |
| --- | --- |
| `Staff::invitation()` | has one `StaffInvitation` |
| account state of a row | owner's own record: "Account owner"; linked account: none shown in onboarding; invitation: "Invitation ready"; email without invitation: "Not invited"; no email: "No email, no account" |
| services summary of a row | not bookable: "Not bookable"; all headquarters services: "All :count services"; none: "None yet"; otherwise ":count services" |

## State of the onboarding

`companies.onboarding_step` moves from 3 to 4 through `CompleteOnboardingStep`, unchanged. Moving
from 2 to 3 now also assigns the owner's staff record to the headquarters and to every service
offered in that run (R2).
