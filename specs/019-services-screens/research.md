# Research: Services screens

## R1. Who sees and who changes

- **Decision**: seeing any screen needs `ServiceView` anywhere; the catalogue is company wide, so every salon shows. Changing anything needs `ServiceManage` for the company, the check `CreateService` and `UpdateService` already make. Controllers check the same, and refuse with 404.
- **Alternatives considered**: restricting a location scoped viewer to their salons (the catalogue has no per salon visibility today).

## R2. Saving the salons and the people

- **Decision**: new action `SyncServiceOffers(author, service, offers)` where `offers` maps a location id to the staff ids doing it there. It creates, reactivates or pauses each `LocationService` and assigns or removes staff through the existing actions. The controller runs `CreateService` or `UpdateService` then `SyncServiceOffers` inside one `DB::transaction`.
- **Rationale**: the form saves one thing in the eyes of the owner; the existing actions each change one offer or one person.
- **Alternatives considered**: extending `UpdateService` (it would grow a second responsibility); one request per checkbox (the design saves once).

## R3. Unticking a salon

- **Decision**: the offer is paused (`UpdateLocationService` with `isActive: false`) and its people removed; its per salon prices stay for the day it is ticked again.
- **Rationale**: `DestroyLocationService` would drop price overrides the owner set elsewhere (D8) without telling them.

## R4. Archive and restore

- **Decision**: `DeactivateService` and `ActivateService`, through `ServiceArchiveController` `create` and `destroy`, as `LocationArchiveController` does for locations.
- **Alternatives considered**: `DestroyService` (deletes the history the design promises to keep).

## R5. Languages

- **Decision**: the form takes the language switch of the onboarding panel (names, descriptions, step names per language); lists read the person's locale through `CatalogueText::resolve`, falling back to the salon's first language.

## R6. Sample statistics

- **Decision**: `SampleServiceStatistics` produces every figure of the design from `crc32` of the service id, the way the design's own hash does, so a service shows the same figures on every visit. It is the only place figures are made up; the page shows "Sample figures. Real ones arrive with appointments." above them.
- **Rationale**: one class to delete when appointments exist (spec FR-011, FR-012).
- **Alternatives considered**: random figures (change on every load and break tests).

## R7. Addresses

- **Decision**: the picked salon is a path segment (`services/locations/{location}`), the search a `q` query string as on the locations screen, and "+ Add a service" of a category its own path (`service-categories/{category}/services/new`).
- **Alternatives considered**: a session value (invisible, not shareable); `?category=` (the project avoids query strings).
