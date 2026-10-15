# Data Model: Salon Currencies

Migrations are edited in place (research R10). Only the changed columns are listed; the rest of
each table is in [003](../003-service-management/data-model.md),
[004](../004-salon-onboarding/data-model.md) and [005](../005-catalogue-languages/data-model.md).

## companies

| Column | Type | Rule |
| --- | --- | --- |
| currency | removed | replaced by `currencies` (R1) |
| currencies | json, cast `array` | ordered list of codes from `jacques.currencies`, at least one, no duplicate; first is the main currency |

## services

| Column | Type | Rule |
| --- | --- | --- |
| price | removed | replaced by `prices` (R2) |
| prices | json, cast `array` | `{code: cents}`; an integer of 0 or more in every company currency |

## location_services

| Column | Type | Rule |
| --- | --- | --- |
| price | removed | replaced by `prices` (R2) |
| prices | json, nullable, cast `array` | `{code: cents}` for the replaced currencies only; null when none is replaced |

## Configuration (`config/jacques.php`)

| Key | Shape |
| --- | --- |
| currencies | list of ISO 4217 codes: `CAD USD EUR GBP CHF` |
| country_currencies | `ISO 3166 alpha-2 => code`; a country not listed takes `CAD` |

## Rules on a price

- A key that is not a current company currency is never written by an action, and never removed.
- A service misses a currency when its `prices` has no key for it.
- The price of a service at a location in a currency is the location's replacement, else the
  service's price, else none (R5).

## Model changes

- `Company`: `currency` out of `$fillable`, `currencies` in, cast to `array`.
- `Service`: `prices` cast to `array`; `missingCurrencies(): list<string>`.
- `LocationService`: `prices` cast to `array`; `resolvedPrice(string $currency): ?int`.
- `Location::services()`, `Service::locations()`: pivot `prices` instead of `price`.
