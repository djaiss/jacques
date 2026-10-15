# Research: Catalogue Languages

## R1. Where a translated text lives

- **Decision**: `service_categories.name`, `service_categories.description`, `services.name` and
  `services.description` become JSON columns keyed by language code, cast to `array`:
  `{"fr": "Coupe femme", "en": "Women's haircut"}`.
- **Rationale**: no join, no new model, no new dependency, and a removed language keeps its key, so
  adding it back restores its texts (FR-011, SC-004).
- **Alternatives**: one translation table per model (rejected: two models and two joins for four
  strings); a polymorphic translation table (rejected: no foreign key); `spatie/laravel-translatable`
  (rejected: a dependency for what a cast does).

## R2. Uniqueness of a name per language

- **Decision**: the database unique index on `company_id, name` goes. The actions check each
  language with `where("name->{$language}", $value)`, which Laravel compiles for SQLite, MySQL and
  PostgreSQL.
- **Rationale**: a JSON path cannot be indexed the same way on the three databases. The actions
  already check uniqueness before writing, and only an Owner writes the catalogue.

## R3. Where the salon languages live

- **Decision**: a `languages` JSON column on `companies`, an ordered list of codes, `["en", "fr"]`.
  The first code is the fallback (spec, Key Entities).
- **Alternatives**: a `company_languages` table (rejected: a model for an ordered list); a key in
  `settings` (rejected: the same reason as 004, R1).

## R4. The offered languages and country defaults

- **Decision**: `config/jacques.php` gains `content_languages` (code, native name, flag as a CSS
  background, like `locales`) for `en fr es pt it de ar zh`, and `country_languages`
  (`CA => [en, fr]`, `US => [en]`, `FR => [fr]`, `BE => [fr]`, `CH => [fr, de]`). Any other
  country takes `[en]`.
- **Rationale**: codes are ISO 639-1, independent of interface locales (`fr_FR`). The interface
  language of a viewer maps to a content language by the part before `_` (FR-012).
- **Flags**: the design pairs English with Canada and French with Quebec, which reads wrongly in
  France. The four languages `locales` already has reuse its flags. Each flag stays decorative
  (`aria-hidden`), and the native name carries the meaning.

## R5. Writing texts through the actions

- **Decision**: `CreateServiceCategory`, `UpdateServiceCategory`, `CreateService` and
  `UpdateService` take `array<string, string> $name` and `array<string, ?string> $description`.
  Validation refuses a code that is not a company language, and a name missing or empty in any
  company language (FR-010). Texts of a language no longer in the company are kept as they are.
- **Logs**: the `name` parameter of the log is the text in the first company language.

## R6. Reading a text

- **Decision**: a pure helper, `App\Helpers\CatalogueText`, with `resolve(array $texts, array
  $languages, string $locale): string` (interface language, then the first company language, then
  the first company language that has a text) and `missingLanguages(array $texts, array
  $languages): array`. `ServiceCategory` and `Service` expose `localizedName()` and `missingLanguages()`
  through it.
- **Rationale**: two models share one rule worth naming.

## R7. Choosing the languages

- **Decision**: new action `UpdateCompanyLanguages(author, company, languages)`, authorized by
  `CompanyManage` (Owner), refusing an empty list, an unknown code and a duplicate. `SetUpSalon`
  gains `languages` and calls it. `CreateCompany` writes `["en"]`; step 1 replaces it with the
  country's languages as soon as the country is chosen.

## R8. Warning before a language is removed

- **Decision**: `LocationViewModel::languagesWithTexts()` lists the company languages holding at
  least one category or service text. On submit, Alpine opens `x-confirm-dialog` when one of
  them was deselected, naming it, and submits on confirmation.
- **Rationale**: principle V. The server does not require a confirmation: nothing is deleted.

## R9. Accessibility of a text in another language

- **Decision**: every per-language field carries `lang="{code}"` and `dir="auto"`.
- **Rationale**: WCAG 3.1.2 (language of parts), and Arabic is written right to left.

## R10. Existing companies (FR-015)

- **Decision**: the migrations are edited in place (pre-production rule, migrations skill). There
  is no production data to convert. Factories and seeders write `["en"]` and English texts.
