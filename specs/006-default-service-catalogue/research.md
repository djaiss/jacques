# Research: Default Service Catalogue

## R1. One action holds the copy, the job only calls it

- Decision: new action `CreateDefaultCatalogue(company)`; the job `CreateDefaultServices(companyId)` loads the company and runs it.
- Rationale: constitution I puts the logic in an action; the job stays a queue wrapper, like `SendEmail`.
- Rejected: logic inside the job (untestable apart from the queue, breaks principle I).

## R2. The action writes the models directly instead of calling `CreateService`

- Decision: `ServiceCategory`, `Service`, `ServiceStep` created with `Model::query()->create()`.
- Rationale: `CreateService` and `CreateServiceCategory` need an author with a permission, refuse a name in a language the company does not have (at signup it has `en` only, FR-010 wants 4), and log one user action per record (40 entries the owner never did).
- Rejected: loosening those actions for one caller (weakens rules that protect the back office and the API).

## R3. Received once, recorded on the company

- Decision: `companies.default_catalogue_created_at`, set in the same transaction as the records, under `lockForUpdate()` on the company.
- Rationale: FR-004. A retry, or a second dispatch, sees the date and returns; a deleted or edited default is never touched again.
- Rejected: looking up services by name (an edit or a deletion would bring a default back); a flag per service (more columns, same answer).

## R4. Offering at the first location converges from both sides

- Decision: new action `OfferServicesAtLocation(location)` inserts a `LocationService` (no price, active) for every active service of the company the location does not offer yet. `CreateDefaultCatalogue` calls it for the headquarters when one exists; `SetUpSalon` calls it right after it creates the first location, not when it updates it.
- Rationale: signup creates no location (004); step 1 does. Whichever of the job and step 1 finishes second does the offering, and the unique index on `location_services` plus the "not offered yet" filter make it safe either way (spec edge cases).
- "Every active service" rather than "every default": until step 1, no screen or API can add a service, so they are the same set, and no marker is needed.
- `CreateLocation` is not changed, so a later location gets nothing (FR-012).
- Rejected: dispatching the job from step 1 (the catalogue would not exist before step 1, against the request).

## R5. Translations come from the interface locale files

- Decision: every name is a literal `__()` call in `CreateDefaultCatalogue`, asked for once per `config('jacques.locales')` key with the `locale` argument, and stored under `Str::before($locale, '_')` (`fr_FR` gives `fr`).
- Rationale: `jacques:localize` scans `app/` and lists the 23 names as missing in `fr_FR`, `de_DE` and `es_ES`; the team translates them like any copy (FR-011). A fifth interface locale fills the defaults automatically.
- Rejected: a separate translation file for the catalogue (a second tooling for the same job).

## R6. Queue, dispatch and failure

- Decision: `CreateDefaultServices::dispatch(companyId: ...)->onQueue('high')`, after the transaction of `CreateCompany`, with `afterCommit()`. `$tries = 3`, `$backoff = 10`.
- Rationale: `high` is where `SendEmail` runs what a person waits for; the owner reaches step 2 within a minute or two. `afterCommit()` keeps the guarantee if `CreateCompany` is ever called inside an outer transaction.
- Failure: no job in the application has a `failed()` method; a failed job is recorded in `failed_jobs` and reported by the exception handler. This job follows that, adding nothing.

## R7. Steps and prices

- Steps: the layout table of the spec maps straight to `active_duration` and `processing_duration`; a simple service is one step of `active_duration` = its duration, `processing_duration` 0.
- Prices: CAD amounts times 100, written as integers in the action (`3500`).
