# Contract: Routes of Step 3

Every route sits in the existing onboarding group of `routes/web.php` (`auth`, `set.locale`,
`welcome.screen`) with `onboarding.step:3`. Controllers live in `App\Http\Controllers\App\Onboarding`
and `...\Onboarding\Team`. Every mutation redirects to `onboarding.staff.index`, except the step
completion. A staff member or role of another company is a 404, covered by a test.

| Verb and URL | Name | Controller@method | Action |
| --- | --- | --- | --- |
| GET `onboarding/staff` | `onboarding.staff.index` | `StaffController@index` | none |
| PUT `onboarding/staff` | `onboarding.staff.update` | `StaffController@update` | `CompleteOnboardingStep`, redirect to step 4 |
| GET `onboarding/staff/members/new` | `onboarding.staff.members.new` | `Team\StaffController@new` | none; the empty panel |
| POST `onboarding/staff/members` | `onboarding.staff.members.create` | `Team\StaffController@create` | `CreateStaff`, `AssignLocation`, `SyncStaffLocationServices`, invitation (R3) |
| GET `onboarding/staff/members/{staff}/edit` | `onboarding.staff.members.edit` | `Team\StaffController@edit` | none; the panel on that person |
| PUT `onboarding/staff/members/{staff}` | `onboarding.staff.members.update` | `Team\StaffController@update` | `UpdateStaffInformation`, `SyncStaffLocationServices`, invitation (R3) |
| DELETE `onboarding/staff/members/{staff}` | `onboarding.staff.members.destroy` | `Team\StaffController@destroy` | `DestroyStaff`; 404 for the author's own record (R8) |

`{staff}` binds by id, with `whereNumber`, as in Settings.

## Panel form (`create` and `update`)

| Field | Rule |
| --- | --- |
| `first_name` | required string, max 255 |
| `last_name`, `display_name`, `job_title` | nullable string, max 255 |
| `email` | nullable email, max 255; ignored for the author's own record |
| `phone` | nullable string, max 255 |
| `bio` | nullable string, max 1000 |
| `is_bookable` | boolean |
| `services` | array of location service ids of the headquarters, may be empty |
| `invite` | boolean; ignored without an email and for the author's own record |
| `role` | integer, required when `invite` is true; the action checks the company |

A failed rule redirects back to the panel's address with errors under the field. A duplicate
email (`InvalidArgumentException` from R4) returns `back()->withErrors(['email' => ...])`.

## Fragments named by `x-target`

`team-list`, `team-summary`, `staff-panel`, `team-footer`. Saving or removing swaps all four;
opening or closing the panel swaps `staff-panel` only.
