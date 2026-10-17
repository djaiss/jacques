<!--
Sync Impact Report
Version change: 1.3.0 -> 1.4.0
Rationale: the suite moves from PHPUnit to Pest (specs/014-pest-migration). The `tests/` constraint
names Pest, and `.claude/rules/testing.md`, which principle III makes binding, now describes Pest
tests, `expect()` assertions and the namespace a test file with helpers declares.

Previous report (1.2.0 -> 1.3.0)
Rationale: D3 now lets a team member exist without an account, so the matching invariant changes.
A team member is a staff record, linked to at most one account, and is deleted only while no
appointment mentions it.

Previous report (1.1.0 -> 1.2.0)
Rationale: `app/Console/Commands` joins the allowed folders for `jacques:localize`, which now exists.

Previous report (1.0.0 -> 1.1.0)
Rationale: decisions.md D1 now allows several locations per salon, so multiple locations leave
the list of excluded features. D4 now keeps the role editor.

Previous report (none -> 1.0.0)
Rationale: first constitution of Jacques, adapted from the Monica constitution 2.5.1. Principles
that only made sense for a personal relationship manager (frozen structure versions, configurable
templates, the module system, record snapshots, reminders) are removed. The product invariants now
come from docs/specs/decisions.md.

Follow-up owed:
- All 14 migrations still define `down()`, which principle IV forbids.
- `lang/de_DE.json` ships, while decision D29 names French, English and Spanish only.
-->
# Jacques Constitution

## Core Principles

### I. Actions Hold the Business Logic

Every user-facing operation MUST live in a single class under `app/Actions`, one action per
operation. Controllers MUST stay thin: validate input, delegate to an action, return a response.
Jobs, commands and API controllers MUST reuse the same actions rather than reimplement the
behavior. View models under `app/ViewModels` assemble what one screen shows. They read from the
database, from configuration and from copy. They MUST NOT write, MUST NOT hold business logic that
belongs to an action, and MUST NOT be consumed anywhere other than Blade.

Rationale: with the logic in one named place per operation, the same rules apply whether the
request comes from the back office, the client portal, the API, a queued job or an Artisan
command, and each rule has exactly one test target.

### II. Simplicity Over Abstraction

Code MUST be written as simply as possible so any contributor can read it without tracing
indirection. Repetition of a few short, non business critical lines is preferred over extracting a
private helper. Extraction is justified only when the logic is non trivial, reused in several
places, or a concept worth naming. Comments MUST explain why something non obvious is done, never
what the code does. The `final` keyword MUST NOT be used.

Rationale: premature abstraction costs more in reading time than the duplication it removes.

### III. Tested Before Merged (NON-NEGOTIABLE)

Every model, controller, action, helper, job, mail and view model MUST have a corresponding test.
No change ships without a test covering the behavior it adds or alters, and the full suite MUST
pass before merge. Tests MUST follow `.claude/rules/testing.md`. Coverage targets the main
functionality and the important edge cases, not every line. Tests MUST NOT contain comments: the
method name carries the intent.

Rationale: tests are the only durable statement of what the product is supposed to do, and they
are what makes an action safe to reuse from a new surface.

### IV. Laravel Conventions Are the Default

Features MUST be built the way Laravel documents them. Any deviation MUST be justified in the pull
request that introduces it. `.claude/rules/code-style.md` is binding. Migrations written by this
project MUST define only `up()`; migrations published by the framework or by a package are left as
they ship. The `env()` helper MUST NOT be used outside `config/`.

Rationale: a contributor who knows Laravel already knows this codebase, and static analysis stays
effective.

### V. Localized and Safe User Experience

Every user-facing string MUST be translatable through `__()` or `trans_choice()`. Translations MUST
be produced by running `php artisan jacques:localize` and translating everything it reports as
missing, in every configured locale. Keys in `lang/*.json` MUST NOT be edited by hand. Dates,
times, numbers, prices and plurals MUST NOT be hardcoded in one language or format. Content written
by a salon or a client MUST NOT be machine translated. Any destructive action in the UI MUST warn
the user before it is performed.

Rationale: an untranslated string is a broken screen for part of the users, and an unconfirmed
destruction is data loss.

### VI. Accessible to Everybody

WCAG 2.2 AA is a delivery condition for every screen, the public booking page first. Every screen a
change touches MUST be operable with the keyboard alone, announced correctly by a screen reader,
free of information carried by colour alone, contrast compliant, focused visibly at all times,
readable zoomed to 200%, and free of animation when the system asks for reduced motion.

Rationale: a client who cannot book online calls the salon or goes elsewhere.

### VII. One Core for Every Surface

The back office, the client portal, the public API, the MCP server and the webhooks MUST rely on
the same actions and the same data (D26). The back office stays in Blade and does not call the
public API over HTTP. A capability is complete when its API coverage exists and is tested, or when
the pull request says why it is missing. The MCP server exposes the API and MUST NOT bypass it or
hold authorization rules of its own. Every action that changes something a salon cares about MUST
be listenable through a webhook.

Rationale: openness is Jacques' position against the platforms that lock salons in (D27), and two
copies of a rule always end up disagreeing.

### VIII. The Written Record Is Short

Every artifact under `specs/` MUST be as short as it can be and still decide what it decides. A fact
MUST appear in exactly one of them; the others link to it rather than restate it.

| Artifact | Cap |
| --- | --- |
| `spec.md` | 120 lines |
| `plan.md` | 100 lines |
| `research.md` | 80 lines |
| `tasks.md` | one line per task, plus phase headings and the dependency list |
| `data-model.md`, `quickstart.md`, `contracts/*` | 60 lines each, omitted when another artifact already carries their content |

- A requirement is one sentence. A rationale that fits nowhere else goes in `research.md`, once.
- A rejected alternative is named in one line, not argued.
- Prefer a table to a paragraph, and a paragraph to several.
- Never restate the specification inside the plan, or the plan inside the tasks.
- Delete rather than qualify.

A cap MAY be exceeded, and the pull request MUST then say which one and why.

Rationale: a specification nobody finishes reading governs nothing, and duplicated passages are the
first to go stale.

## Product Invariants

These come from `docs/specs/decisions.md`. A change MUST NOT bend them. Reversing one is a change to
that file first, then an amendment here.

- Jacques builds less. A feature `decisions.md` excludes (resources, recurring
  appointments, waiting list, products and stock, import and export, audit log) is not built until
  that file changes.
- Jacques never touches money: no payment, no deposit, no till, no record of what was paid (D22).
- An automation never takes an operational decision silently. Jacques never cancels an appointment
  on its own (D13).
- Booking never requires a client account. A secure link speaks about one appointment and stops
  working with it (D18, D21).
- The availability engine MUST be correct and protected against two simultaneous bookings of the
  same slot, because no human approves an online booking (D10). Validating the SMS code turns the
  held slot into an appointment atomically (D31).
- A phone number identifies exactly one client record, normalized to the international format
  before any comparison (D18).
- Times are stored in UTC and shown in the time zone of the salon (D2). The public booking page
  speaks the language of the salon, not the browser's (D30).
- A team member is a staff record, linked to at most one user account. Somebody who leaves is
  deactivated, and deleted only while no appointment mentions them (D3). An erasure request anonymizes the client record and keeps its appointments (D23).

## Technology and Architecture Constraints

- Backend: PHP 8.4 or later on the current major version of Laravel. Frontend: Blade, Tailwind CSS,
  Alpine.js, Alpine Ajax, Turbo and instant.page. A single page application framework is out of
  scope.
- New code goes into an existing folder. A new top level folder under `app/` requires an amendment.
  - `app/`: `Actions`, `Console/Commands`, `Enums`, `Helpers`, `Http` (`Controllers`, `Middleware`), `Interfaces`,
    `Jobs`, `Mail` (one class per email the product sends), `Models`, `Permissions`, `Providers`,
    `Rules`, `View/Components` and `ViewModels`. `Http/Controllers/Api`, `Http/Resources` and the
    MCP folder arrive with the public API (D27).
  - `routes/`: `web.php` for the signed in application, `auth.php` for the credentials screens,
    `console.php` for scheduled work. `api.php` arrives with the public API.
  - `tests/`: `Unit` mirroring the application namespace, `Feature` for controllers, `Fixtures` for
    any class a test needs. The suite runs on Pest, and a test file declares no class.
  - `docs/specs/`: the product specification. `specs/`: the Spec Kit artifacts of each feature.
- Data access MUST be scoped to the company the user belongs to. A request for a record of another
  company MUST fail as if the record did not exist, and that failure MUST be covered by a test.
- Webhook delivery is a background concern. A slow or broken endpoint MUST NOT slow down a save.
  Sends retry with spacing, every event carries a unique identifier, and repeated failure
  deactivates the subscription with an explanation.
- The back office MUST be usable from a phone.
- The suite MUST pass on SQLite, MySQL and PostgreSQL. Database specific SQL is not allowed unless
  it is guarded per driver.
- Static analysis runs through Larastan at PHPStan level 5 over `app/`, `routes/` and `database/`.
  Findings are fixed, not ignored, unless the pull request justifies the ignore.

## Development Workflow and Quality Gates

- Every task starts on a new branch cut from an up to date `main`, named `YYYY-MM-DD-{name}`.
- Commits and pull request titles follow Conventional Commits, and the subject reads as one short
  sentence naming the outcome: `feat: better header buttons`, not
  `feat: draw the top bar controls so they read against the bar`.
- Documentation and any prose this project writes MUST NOT use dashes as punctuation.
- Claude Code MUST NOT be mentioned in commits, pull requests or issue comments.
- Before a change is considered done: tests exist and pass, `php artisan jacques:localize` has been
  run and the reported strings translated in every locale, Pint is clean, PHPStan reports nothing
  new, and every screen it touches meets principle VI on desktop, tablet and phone.
- CI is the gate: `semantic.yml` checks the pull request title, `static.yml` runs Pint and PHPStan,
  and `tests.yml` runs the suite on the three databases. A red pipeline blocks merge.
- When an implementation is unclear, ask before writing code.

## Governance

This constitution decides how Jacques is built. `docs/specs/decisions.md` decides what Jacques does,
and wins over every other specification. Neither may be contradicted silently. Where this file
conflicts with what the codebase does, the pull request that discovers it MUST say which one is
wrong and fix it.

Amendments are pull requests that change this file and state what changed and why. Operational
guidance (naming, test helpers, style details) belongs in `.claude/CLAUDE.md` and `.claude/rules/`,
which MUST stay consistent with the principles above.

Versioning follows semantic versioning: MAJOR for removing or redefining a principle, MINOR for a
new principle or materially expanded guidance, PATCH for wording that changes no obligation.

A reviewer MUST be able to point at the action, the test and the translations for the change under
review, and say which principles it had to satisfy.

**Version**: 1.4.0 | **Ratified**: 2026-10-14 | **Last Amended**: 2026-10-16
