# Data Model: Catalogue Languages

Migrations are edited in place (research R10). Only the changed columns are listed; the rest of
each table is in [003](../003-service-management/data-model.md) and
[004](../004-salon-onboarding/data-model.md).

## companies

| Column | Type | Rule |
| --- | --- | --- |
| languages | json, cast `array` | ordered list of codes from `jacques.content_languages`, at least one, no duplicate; first is the fallback |

## service_categories

| Column | Type | Rule |
| --- | --- | --- |
| name | json, cast `array` | `{code: text}`; non-empty in every company language; unique per company per language |
| description | json, nullable, cast `array` | `{code: ?text}`; optional in each language |

The unique index on `company_id, name` is removed (R2).

## services

| Column | Type | Rule |
| --- | --- | --- |
| name | json, cast `array` | as `service_categories.name` |
| description | json, nullable, cast `array` | as `service_categories.description` |

The unique index on `company_id, name` is removed (R2). `service_steps` is unchanged.

## Configuration (`config/jacques.php`)

| Key | Shape |
| --- | --- |
| content_languages | `code => [label (native name), flag (CSS background)]`, for `en fr es pt it de ar zh` |
| country_languages | `ISO 3166 alpha-2 => list of codes`; a country not listed takes `[en]` |

## Rules on a text

- A key that is not a current company language is never written by an action, and never removed.
- A text is read with `CatalogueText::resolve()`: the viewer's interface language, then the first
  company language, then the first company language that holds a text (R6).
- An item misses a language when its name in that language is absent or empty.

## Model changes

- `Company`: `languages` in `$fillable` and casts.
- `ServiceCategory`, `Service`: `name` and `description` cast to `array`; `localizedName(string $locale)`
  and `missingLanguages()` methods through `CatalogueText`.
