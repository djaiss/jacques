# Contract: Routes of Step 4 and the Dashboard

Step routes sit in the existing onboarding group of `routes/web.php` (`auth`, `set.locale`,
`welcome.screen`) with `onboarding.step:4`. Controllers live in `App\Http\Controllers\App\Onboarding`
and `...\Onboarding\Availability`. A staff member of another company, inactive, not bookable or
not assigned to the headquarters is a 404, covered by a test.

| Verb and URL | Name | Controller@method | Action |
| --- | --- | --- | --- |
| GET `onboarding/availability` | `onboarding.availability.index` | `AvailabilityController@index` | none |
| PUT `onboarding/availability` | `onboarding.availability.update` | `AvailabilityController@update` | R4, redirect to `dashboard.index` |
| GET `onboarding/availability/staff/{staff}/edit` | `onboarding.availability.staff.edit` | `Availability\StaffHoursController@edit` | none; the panel on that person |
| PUT `onboarding/availability/staff/{staff}` | `onboarding.availability.staff.update` | `Availability\StaffHoursController@update` | `SyncStaffWorkingHours`, follows false; redirect to the index |
| GET `dashboard` | `dashboard.index` | `DashboardController@index` | none; placeholder |

`{staff}` binds by id, with `whereNumber`. The routes `onboarding.appointments.*` are removed.
`dashboard` sits in its own group with `auth`, `set.locale` and `welcome.screen`.

## Week form (both PUT routes)

| Field | Rule |
| --- | --- |
| `days` | required array with keys 0 to 6 |
| `days.*.open` | boolean |
| `days.*.ranges` | array, required with at least one item when `open` is true |
| `days.*.ranges.*.start`, `days.*.ranges.*.end` | required string, in the 30 minute grid from `06:00` to `22:00` |
| `use_opening_hours` | boolean; `update` of the step only |

A closed day's ranges are ignored. A failed rule, or an `InvalidArgumentException` from an action,
redirects back with errors under `days`.

## Fragments named by `x-target`

`team-hours`, `hours-panel`, `availability-footer`. Saving the panel swaps all three; opening or
closing the panel swaps `hours-panel` only. The opening hours section is never swapped (R6).
