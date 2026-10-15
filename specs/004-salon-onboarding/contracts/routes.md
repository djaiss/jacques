# Routes: Salon Onboarding

Every route sits in a group with `auth`, `set.locale` and `welcome.screen`, and carries
`onboarding.step` with its own step as parameter (none for `onboarding.index`).

| Verb | URL | Controller | Name | Result |
| --- | --- | --- | --- | --- |
| GET | `onboarding` | `Onboarding\OnboardingController@index` | `onboarding.index` | redirect to the saved step |
| GET | `onboarding/location` | `Onboarding\LocationController@index` | `onboarding.location.index` | form, or confirmation once step 2 is reached |
| PUT | `onboarding/location` | `Onboarding\LocationController@update` | `onboarding.location.update` | `SetUpSalon`, redirect to `onboarding.location.index` |
| GET | `onboarding/services` | `Onboarding\ServiceController@index` | `onboarding.services.index` | placeholder step |
| PUT | `onboarding/services` | `Onboarding\ServiceController@update` | `onboarding.services.update` | `CompleteOnboardingStep`, redirect to staff |
| GET | `onboarding/staff` | `Onboarding\StaffController@index` | `onboarding.staff.index` | placeholder step |
| PUT | `onboarding/staff` | `Onboarding\StaffController@update` | `onboarding.staff.update` | `CompleteOnboardingStep`, redirect to appointments |
| GET | `onboarding/appointments` | `Onboarding\AppointmentController@index` | `onboarding.appointments.index` | placeholder step |
| PUT | `onboarding/appointments` | `Onboarding\AppointmentController@update` | `onboarding.appointments.update` | `CompleteOnboardingStep`, redirect to `home.index` |

`WelcomeController@update` now redirects to `onboarding.index` instead of `home.index`.

## PUT onboarding/location fields

| Field | Rules |
| --- | --- |
| `name` | required, string, max:255 |
| `address_line_1`, `city`, `region` | nullable, string, max:255 |
| `postal_code` | nullable, string, max:20 |
| `phone` | nullable, string, max:50 |
| `country` | required, string, size:2, in the country list |
| `timezone` | required, string, in the time zones of `country` |

The empty name message is "The salon needs a name."

## Middleware `onboarding.step`

| Request | Result |
| --- | --- |
| user not owner | redirect to `home.index` |
| company not pending | redirect to `home.index` |
| step beyond the saved one | redirect to the saved step's route |
| no step parameter, or step up to the saved one | passes |
