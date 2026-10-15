# Research: Salon Onboarding

## R1. Where the state lives

- **Decision**: two columns on `companies`: `is_onboarding_pending` (boolean, default false) and
  `onboarding_step` (small integer, default 1). `CreateCompany` sets the flag to true.
- **Rationale**: mirrors `is_welcome_screen_pending`. A false default keeps factory and seeded
  companies out of the flow, which is FR-002 for existing companies, since the migration is edited
  in place before production.
- **Alternatives**: one nullable `onboarding_step` where null means finished (rejected: "done"
  is implicit); a key in `settings` (rejected: cannot be cast or queried plainly).

## R2. What "saved step" means

- **Decision**: `onboarding_step` is the furthest step reached. Completing step n raises it to
  n + 1, never lowers it. "Finish later" is a plain link to home and writes nothing.
- **Rationale**: opening a page never writes, so revisiting step 1 cannot move the owner back,
  and closing the browser loses nothing.

## R3. Steps as an enum

- **Decision**: `OnboardingStepEnum`, int backed: `Location = 1`, `Services = 2`, `Staff = 3`,
  `Appointments = 4`, with `label()` and `routeName()`.
- **Rationale**: one list drives the tracker, the middleware, the redirects and the next step.

## R4. Access and step gate

- **Decision**: one middleware, `OnboardingStepGate`, alias `onboarding.step`, with the step number as
  parameter (`onboarding.step:2`). Not owner, or onboarding not pending: redirect home.
  Step beyond the saved one: redirect to the saved step. `onboarding.index` uses it without a
  parameter, and its controller redirects to the saved step.
- **Rationale**: the middlewares skill keeps access rules out of controllers. `welcome.screen`
  stays on the group, so the welcome screen still comes first.
- **Alternatives**: one middleware per rule (rejected: two classes for one check).

## R5. Step 1 action

- **Decision**: `SetUpSalon(author, name, country, region, city, postalCode, addressLine1, phone,
  timezone)`. In one transaction, with the company row locked: `UpdateCompany` with the new name
  and its other fields unchanged; then the first location (the headquarters, else the location
  already named like the salon) goes through `UpdateLocation`, or `CreateLocation` when none
  exists; then `CompleteOnboardingStep(step: Location)`.
- **Rationale**: reuses the actions that already authorize, sanitize, validate and log. The lock
  is what keeps two tabs from creating two locations (SC-005).
- `CreateLocation` gains `isHeadquarters = false`, mirroring `UpdateLocation`.
- `UpdateLocation` is given the location's current `address_line_2` and `email`, which the form
  does not carry, so they are not cleared.

## R6. Completing a step

- **Decision**: `CompleteOnboardingStep(author, step)`. Raises `onboarding_step` to the next step;
  on `Appointments`, sets `is_onboarding_pending` to false and logs `OnboardingCompleted` once.
- **Rationale**: steps 2 to 4 have no content yet; one action covers every "continue" button and
  stays the place their future content will call.

## R7. Countries and time zones

- **Decision**: countries are the `country_code` values of `DateTimeZone::listIdentifiers()`,
  named with `Locale::getDisplayRegion()` in the user's locale and sorted with `Collator`. Time
  zones per country come from `DateTimeZone::listIdentifiers(PER_COUNTRY, $code)`, labelled with
  `IntlTimeZone::getDisplayName()` (generic name) plus the city of the identifier. The map is
  handed to Alpine, which filters the second select.
- **Rationale**: `intl` is installed; no package, no copy to translate, every country covered.
- **Validation**: country `size:2` within the list; time zone in `listIdentifiers(PER_COUNTRY,
  country)`, so the pair always matches.
- No country is preselected; the time zone select is disabled until one is chosen.

## R8. Visible deviations from the design

- The phone placeholder does not follow the country (that needs a phone metadata package). No
  placeholder is shown.
- The salon name field is prefilled with the company name instead of "Studio Camille".
- The confirmation is the step 1 page itself once step 2 is reached; "Edit the salon" swaps it for
  the prefilled form with Alpine, without a request.

## R9. View models

- **Decision**: `Onboarding\StepViewModel(user, step)` gives the header and the tracker; steps 2
  to 4 use it as is. `Onboarding\LocationViewModel` extends it with the form values, the countries
  and the time zone map.
