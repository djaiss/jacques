# Data Model: Salon Onboarding

## companies (existing table, two new columns)

| Column | Type | Default | Comment |
| --- | --- | --- | --- |
| `is_onboarding_pending` | boolean | false | whether the company still has to go through the onboarding |
| `onboarding_step` | unsigned tiny integer | 1 | furthest onboarding step the company reached, from 1 to 4 |

- Added to `0000_01_01_000000_create_companies_table.php`, after `is_welcome_screen_pending`.
- `Company`: `$fillable`, casts (`boolean`, `OnboardingStepEnum`), `@property` lines.

## locations (unchanged)

The first location is the one with `is_headquarters = true`. Step 1 writes `name`, `country`,
`region`, `city`, `postal_code`, `address_line_1`, `phone` and `timezone`.

## OnboardingStepEnum (int backed)

| Case | Value | Label | Route |
| --- | --- | --- | --- |
| `Location` | 1 | Location | `onboarding.location.index` |
| `Services` | 2 | Services | `onboarding.services.index` |
| `Staff` | 3 | Staff | `onboarding.staff.index` |
| `Appointments` | 4 | Appointments | `onboarding.appointments.index` |

## State transitions

| From | Event | To |
| --- | --- | --- |
| (none) | `CreateCompany` | pending, step 1 |
| (none) | factory, seeder | not pending, step 1 |
| pending, step n | `CompleteOnboardingStep(step: k)`, k < 4 | pending, step max(n, k + 1) |
| pending | `CompleteOnboardingStep(step: Appointments)` | not pending, logs `OnboardingCompleted` |
| not pending | `CompleteOnboardingStep` | unchanged, nothing logged |

No transition goes back to pending.

## UserActionEnum

| Case | Value | Label |
| --- | --- | --- |
| `OnboardingCompleted` | `onboarding_completed` | Completed the onboarding |
