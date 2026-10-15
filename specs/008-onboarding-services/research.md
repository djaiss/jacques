# Research: Onboarding Step 2, Services

## R1. Steps carry a name, a duration and a team member flag

- Decision: `service_steps` drops `active_duration` and `processing_duration` for `name` (json, nullable), `duration` and `requires_staff`. The existing migration is edited (migrations skill, until production).
- D5 in `docs/specs/decisions.md` is amended first, then spec 003 FR-005 is superseded by this feature (governance: decisions.md wins).
- The default catalogue keeps its timings: `30 + 30 processing, 30` becomes three steps, busy, free, busy. Step names ("Color application", "Processing", "Rinse & finish", "Application") go through `__()` like the other default texts.
- A service keeps at least one step with `requires_staff`. A one step service has no step name: the panel and the counts ignore it.
- Rejected: keeping active plus processing pairs (cannot hold names, nor a free step first).

## R2. Missing texts and prices are allowed

- Decision: `CreateService`, `UpdateService`, `CreateServiceCategory` and `UpdateServiceCategory` stop requiring a name in every language and a price in every currency. Empty texts are dropped from the json, so a missing language is an absent key, as a missing currency already is (`Service::missingCurrencies()`).
- Uniqueness per language applies only to written names. A price, when given, stays 0 or more.
- `Service::isBookableIn(string $currency)` states the booking rule of FR-005, for the booking feature to use.

## R3. Live saving through small autosaving forms

- Decision: each editable thing is its own form (a category name, a service row's duration, a service row's prices, the panel). Alpine submits it on `change`; Alpine Ajax swaps the fragments it names (`x-target`): the row or category, the language cards, the summary and the footer.
- The controller redirects back to `onboarding.services.index` with `catalogue_saved` flashed; the footer reads it and fades "Saved" after two seconds. The usual `status` key is not used, since it would raise a toast on every autosave.
- Rejected: one big form for the page (a change in one field would resubmit everything and lose focus); a JSON API (the constitution keeps the back office in Blade).

## R4. Language switching is client side

- Decision: every text input is rendered once per salon language and shown with `x-show` for the picked language, kept in Alpine state on the page root. Picking a language sends no request, and a form always submits every language, so `UpdateService` keeps its "replace everything" contract.

## R5. Undo keeps a snapshot in the session

- Decision: before a removal, the controller asks `CatalogueSnapshot` (helper, read only) for what will go: the category or service rows with their steps, ranks and texts. The snapshot and a sentence naming it are put in the session under `onboarding.removal`; every other catalogue mutation forgets that key in one line. Flash data was rejected: it dies on the redirect that follows the removal, before the owner can click "Undo". `RestoreCatalogueSnapshot` recreates the rows and shifts ranks to put them back in place.
- Recreated rows get new ids. Their location offers come back at step completion (R7). Team members assigned at step 3 to a removed service are not restored; at that point the owner has gone back to step 2 on purpose.
- Rejected: soft deletes (a global scope on two models used everywhere, and a purge job for what nobody restores).

## R6. Removal actions

| Need | Action |
| --- | --- |
| remove a service | `DestroyService` (existing) |
| remove a category and its services | `DestroyServiceCategory` gains `withServices: bool`, default false keeps today's behaviour |
| empty the catalogue | `DestroyCatalogue` (new): every category and service of the company |
| restore the example | `RestoreDefaultCatalogue` (new), refused unless the catalogue is empty |
| move a category | `UpdateServiceCategoryPosition` (existing), position plus or minus one |
| change a row's duration or prices | `UpdateServiceDuration`, `UpdateServicePrices` (new); the panel uses `UpdateService` |

The example catalogue's content moves from `CreateDefaultCatalogue` to `App\Helpers\DefaultCatalogue`, written by a new system action `CopyDefaultCatalogue` that both actions call. `CreateDefaultCatalogue` keeps its once only guard.

## R7. Offering services at the first location

- Decision: `CompleteOnboardingStep` runs `OfferServicesAtLocation` on the headquarters when the step is `Services`, every time, even when the step was already completed. It skips what is already offered.
- Rejected: offering inside `CreateService` (spec 003: a service is offered nowhere until a location adds it).

## R8. Prices

- Decision: inputs show and accept major units with the currency's decimals (all two today, `config/jacques.php`). The controller validates `nullable|decimal:0,2|min:0` and converts to cents with `(int) round($value * 100)`; an empty field means no price.
- The visible prefix is a fixed symbol per currency (`CA$`, `US$`, `€`, `£`, `CHF`), since the locale symbol for USD in English is a bare `$`; the currency name is the `aria-label` and the `title`, as spec 007 requires a name.

## R9. Confirmation and undo

- Removing a category with services and emptying the catalogue use `x-confirm-dialog` first (principle V), then also offer undo. Removing one service or one step is immediate with undo; recorded in Complexity Tracking.

## R10. API and webhooks

- No API and no webhook exist yet (constitution VII); the pull request says so, as spec 009 did.
