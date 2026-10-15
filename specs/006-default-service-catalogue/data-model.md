# Data Model: Default Service Catalogue

## Changed table

| Table | Column | Type | Notes |
| --- | --- | --- | --- |
| `companies` | `default_catalogue_created_at` | datetime, nullable | When the default catalogue was copied; null until then, and for ever when it never was (R3) |

Edited in place in `0000_01_01_000000_create_companies_table.php`, as 005 did (pre-production).
`Company` gains the cast to `datetime`, the `@property`, and the column in `$fillable`.

## Records written per company

| Model | Count | Values |
| --- | --- | --- |
| `ServiceCategory` | 6 | `name` in `en`, `fr`, `de`, `es`; `description` null; `position` 1 to 6 |
| `Service` | 17 | `name` in the same 4 languages; `price` in cents; `is_active` true; `position` from 1 in each category |
| `ServiceStep` | 20 | one per simple service (14), two for each of the 3 services of the step table (6) |
| `LocationService` | 17 | headquarters only; `price` null; `is_active` true (R4) |

Values are the spec's Default catalogue tables. No table other than `companies` changes.

## Invariants

- The sum of `active_duration + processing_duration` of a service's steps equals its duration in the table.
- Every record carries the company of the job, and every `LocationService` joins a location and a service of that company.
- `default_catalogue_created_at` is set in the transaction that writes the records, never outside it.
