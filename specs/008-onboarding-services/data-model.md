# Data Model: Onboarding Step 2, Services

Only `service_steps` changes shape. `services` and `service_categories` keep their columns; what
changes is which values their actions accept (research R2).

## service_steps (edited migration `2026_10_14_000002`)

| Column | Type | Rule |
| --- | --- | --- |
| id | bigint | primary key |
| service_id | bigint | cascade on delete |
| position | unsigned int | rank from 1, unique with `service_id` |
| name | json, nullable | text per salon language, keyed by language code; absent key means missing |
| duration | unsigned int | minutes, at least 1 |
| requires_staff | boolean | true when the team member is busy during the step |
| timestamps | | |

Removed: `active_duration`, `processing_duration`.

## Rules carried by the actions

| Rule | Where |
| --- | --- |
| at least one step, and at least one with `requires_staff` | `CreateService`, `UpdateService` |
| a written name is unique per language in the company | the four catalogue actions (R2) |
| a price, when given, is 0 or more, in cents | `CreateService`, `UpdateService` |
| a step name is a language of the salon | `CreateService`, `UpdateService` |

## Derived values (models)

| Value | Definition |
| --- | --- |
| `Service::duration` | sum of `steps.duration` |
| `Service::busyDuration` | sum of `steps.duration` where `requires_staff` |
| `Service::freeDuration` | `duration` minus `busyDuration` |
| `Service::missingLanguages()` | languages lacking the name, the description when written in any language, or a step name when the service has more than one step |
| `Service::missingCurrencies()` | existing, unchanged |
| `Service::isBookableIn($currency)` | price present in `$currency` and name present in the first salon language |

## Session: `onboarding.removal`

| Key | Content |
| --- | --- |
| `message` | sentence naming what went, already translated |
| `categories` | removed categories: name, description, position |
| `services` | removed services: category position or null, name, description, prices, position, steps |

Present from a removal until "Undo" or the next catalogue mutation (R5).

## State of the onboarding

Unchanged: `companies.onboarding_step` moves from 2 to 3 through `CompleteOnboardingStep`, which now
also offers every service at the headquarters (R7).
