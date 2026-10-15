# Research: Salon Currencies

## R1. Where the salon currencies live

- **Decision**: a `currencies` JSON column on `companies`, an ordered list of ISO 4217 codes,
  `["CAD", "USD"]`, cast to `array`. It replaces the `currency` column, and `UpdateCompany` loses
  its `currency` argument. The first code is the main currency (spec, Key Entities).
- **Rationale**: the same shape as `languages` (005, R3), read the same way.
- **Alternatives**: keep `currency` as the main one beside a list (rejected: one fact in two
  places); a `company_currencies` table (rejected: a model for an ordered list).

## R2. Where a price lives

- **Decision**: `services.price` becomes `services.prices`, JSON keyed by currency code, cast to
  `array`: `{"CAD": 6500, "USD": 4800}`. `location_services.price` becomes
  `location_services.prices`, nullable JSON holding only the currencies the location replaces.
- **Rationale**: a removed currency keeps its key, so adding it back restores its prices (FR-010,
  SC-003), exactly as texts do (005, R1). A location replacing one currency leaves the others
  following the service (FR-009).
- **Alternatives**: a `service_prices` table (rejected: a model and a join for one integer per
  currency); one column per currency (rejected: the list is configuration).

## R3. The offered currencies and country defaults

- **Decision**: `config/jacques.php` gains `currencies` (`CAD USD EUR GBP CHF`) and
  `country_currencies` (`CA => CAD`, `US => USD`, `FR => EUR`, `BE => EUR`, `CH => CHF`). Any other
  country takes `CAD`.
- **Names**: the configuration holds codes only. `LocationViewModel` names each currency through
  `__()` (`Canadian dollar`), since configuration is read before a locale is known.
- **Minor units**: all five have two decimals, so every amount is stored in cents (FR-007). A
  currency with another exponent is a change to this decision when it is offered.

## R4. Choosing the currencies

- **Decision**: new action `UpdateCompanyCurrencies(author, company, currencies)`, a copy of
  `UpdateCompanyLanguages`: `CompanyManage` (Owner), refuses an empty list, an unknown code and a
  duplicate, logs `CompanyCurrenciesUpdated` on the `low` queue. `SetUpSalon` gains `currencies`
  and calls it. `CreateCompany` writes `["CAD"]`.

## R5. Writing prices through the actions

- **Decision**: `CreateService` and `UpdateService` take `array<string, int> $prices`. Validation
  refuses a code that is not a company currency, a company currency without a price, and a
  negative amount. `UpdateService` keeps the keys of removed currencies, as it keeps texts.
- **Decision**: `CreateLocationService` and `UpdateLocationService` take `array<string, int>
  $prices`, the replaced currencies only; `[]` follows the service everywhere and is stored as
  null. Same refusals, minus the required one.
- **Reading**: `Service::missingCurrencies()` lists the company currencies without a price, and
  `LocationService::resolvedPrice(string $currency): ?int` returns the replacement, else the
  price of the service, else null. Neither needs a helper: each is three lines.

## R6. The default catalogue

- **Decision**: `CreateDefaultCatalogue` writes the same amount under every code of
  `jacques.currencies` (FR-015). It runs at sign up, when the company holds `["CAD"]` only, so it
  cannot know what step 1 will choose: writing every offered currency means a salon choosing euro
  finds its default services already priced. Keys outside the salon currencies stay hidden, as for
  a removed currency.
- **Rejected**: converted or per market amounts (D22, nothing is converted, and a table of five
  price lists to maintain); Canadian dollar only (every non Canadian salon starts with 17 services
  missing a price).

## R7. Warning before a currency is removed

- **Decision**: `LocationViewModel::currenciesWithPrices()` lists the company currencies holding a
  price in a service or a location. The existing removal dialog of step 1 names the removed
  languages and the removed currencies together, one sentence each, and submits on confirmation.
- **Rationale**: one submission, one dialog. The server requires nothing: nothing is deleted.

## R8. Showing a price

- **Decision**: deferred to the first screen that shows one. It will use `Number::currency()`
  with the viewer's locale, which needs the `intl` extension; the code names the currency, never
  a bare symbol (spec, Edge Cases).

## R9. Accessibility

- **Decision**: the currency toggles copy the language toggles (`aria-pressed`, a group labelled
  by its heading). Each shows the translated name and the code, `Canadian dollar · CAD`, so two
  dollars never read alike.

## R10. Existing companies (FR-013)

- **Decision**: migrations are edited in place (pre-production, migrations skill); there is no
  production data. Factories write `["CAD"]` and CAD prices; no seeder writes a price.
