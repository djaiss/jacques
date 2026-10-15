# Research: Welcome Screen

## R1. Where the flag lives

- **Decision**: a boolean column `is_welcome_screen_pending` on `companies`, default false.
- **Rationale**: the request names the company model. The `is_` prefix matches `is_active`,
  `is_default` and `is_headquarters`. True means the screen is still owed, so pressing the button
  sets it to false, as asked.
- **Alternatives**: a key in `companies.settings` (rejected: the request asks for a flag on the
  model, and a JSON key cannot be cast or queried as plainly); a column on `users` (rejected: the
  request names the company).

## R2. Default value and existing companies

- **Decision**: the column defaults to false; `CreateCompany` sets it to true.
- **Rationale**: the migration is edited in place (pre-production rule), so no data needs a
  backfill. A false default keeps seeded and factory companies out of the welcome screen, which
  satisfies FR-002 and keeps every existing feature test free of redirects.

## R3. How the user is sent to the screen

- **Decision**: a route middleware, `RedirectToWelcomeScreen`, placed on home and on the signed in
  groups of `routes/web.php`.
- **Rationale**: the middlewares skill puts access rules in middleware, not controllers.
  `routes/auth.php` stays outside it, so the email confirmation flow and sign out are untouched,
  which is what Q1 answer B asks.
- **Alternatives**: appending to the global `web` group (rejected: it would also catch the
  confirmation and sign out routes and need an exclusion list); changing the redirect target of
  `EmailVerificationController` (rejected: a user who leaves the notice without confirming would
  never see the screen).

## R4. Home is a public route

- **Decision**: the middleware passes through when `$request->user()` is null.
- **Rationale**: `home.index` has no `auth` middleware today and is where email confirmation lands.
  Guests keep reaching it unchanged.

## R5. Routes and controller methods

- **Decision**: `GET welcome` → `index`, `PUT welcome` → `update`, names `welcome.index` and
  `welcome.update`.
- **Rationale**: only the allowed method names; the button changes a value on the company, which
  is an update. Names follow `home.index`.
- **Alternatives**: `DELETE welcome` → `destroy` (rejected: nothing is deleted).

## R6. Action

- **Decision**: `DismissWelcomeScreen(user: User)`, working on `$user->company`, logging
  `WelcomeScreenDismissed` only when the flag changed.
- **Rationale**: no record id comes from the request, so no other company can be reached. Logging
  once keeps the journal truthful when two tabs are open.
- **Alternatives**: reuse `UpdateCompany` (rejected: it edits the company profile and logs
  `CompanyUpdated`, which would mislabel the event).

## R7. View

- **Decision**: `x-guest-layout` without the top bar; existing tokens (`page`, `canvas`, `ink`,
  `body`, `muted`, `hairline`) already match the design; `x-logo` for the logo; `x-avatar-initials`
  with name "Regis" for the signature, hidden from screen readers since the name is written beside
  it; `x-button` inside `x-form` with the arrow icon marked `aria-hidden`.
- **Rationale**: no new component and no new colour. The design's round coral avatar becomes the
  round `neutral` tone of `x-avatar-initials` (the `accent` tone is a square tile). This is a
  visible colour deviation from the design, accepted to avoid a new tone for one screen.
- The proper name "Regis" is not translated; every other string is.
