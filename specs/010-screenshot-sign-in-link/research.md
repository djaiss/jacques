# Research: Screenshot Sign In Link

## R1. Where the guard lives

**Decision**: a static helper, `App\Helpers\ScreenshotLink`, with `isAvailable(): bool` and
`isDevelopmentHost(string $host): bool`. The command and the controller both call it.

**Rationale**: the same two checks run in two places, and they are a concept worth naming
(principle II allows the extraction). `app/Helpers` already holds static helpers such as
`RecoveryCodes`.

**Alternatives considered**: static methods on the controller, as in the reference project
(a command reaching into a controller); an action (nothing is written, so no operation to own).

## R2. No action class

**Decision**: the controller signs in with `Auth::guard('web')->login()` and redirects, with no action.
`MagicLinkController::show` already logs in from the controller. The link changes no
record, sends nothing and logs nothing a salon cares about, so principle I has no operation to
hold. Logging a `UserSignedIn` action or stamping `last_signed_in_at` would pollute the record of a
development account with captures. A `SignInWithScreenshotLink` action would wrap one line.

## R3. Signing and expiry

**Decision**: `URL::temporarySignedRoute()` with the `signed` middleware on the route. Lifetime from
`jacques.screenshot_link_duration_minutes`, default 5, beside `magic_link_duration_minutes`.

**Rationale**: the signature covers every query parameter and the expiry, so an edited or stale
link answers `403` without code of ours. The signature uses the application key, so only somebody
who can run the application can mint one.

**Alternatives considered**: a stored token like magic links, rejected (a table for a dev tool).

## R4. Production and the route

**Decision**: the route is always registered; the controller answers `404` unless
`ScreenshotLink::isAvailable()` and the request host is a development host.

**Rationale**: matches `LocalSignInController`, keeps `route:cache` predictable, and a `404` is
indistinguishable from a missing route.

**Alternatives considered**: registering the route conditionally, rejected (environment dependent
route list, breaks `route()` in tests run as production).

## R5. Development hosts

**Decision**: `localhost`, a name ending in `.localhost` or `.test`, `127.0.0.1` and `::1` (with or
without brackets). Herd serves `.test`; loopback covers `php artisan serve`.

## R6. Destination path

**Decision**: accept `to` only when it starts with one `/` and not `//` or `/\`; otherwise land on
`/`. Redirect with `redirect($to)`, which resolves against `APP_URL`.

**Rationale**: blocks open redirects to another site (spec, User Story 3).

## R7. Middleware

**Decision**: `['signed', 'set.locale']`, no `guest`.

**Rationale**: `guest` would bounce a browser already signed in, while the spec says the link
replaces that session. The session is regenerated after login.

## R8. Two factor and inactive accounts

**Decision**: the link skips the two factor challenge and refuses nothing beyond a missing account.

**Rationale**: decided in the spec. `MagicLinkController` never skips the challenge; this link may
because it cannot exist outside a development instance.

## R9. Console copy

**Decision**: plain English strings, not `__()`, as `LocalizeCommand` does.

## R10. Friends references

**Decision**: no code change. A search found none in `tests/` or `database/`. The take-screenshot
skill is rewritten (host `jacques.test`, address `michael.scott@dundermifflin.com`, a screen of
this application such as `/settings/administration/locations`).
