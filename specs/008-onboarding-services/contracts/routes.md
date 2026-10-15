# Contract: Routes of Step 2

Every route sits in the existing onboarding group of `routes/web.php` (`auth`, `set.locale`,
`welcome.screen`) with `onboarding.step:2`. Controllers live in `App\Http\Controllers\App\Onboarding`
and `...\Onboarding\Catalogue`. Every mutation redirects to `onboarding.services.index` with
`status` = "Saved" (R3), except the step completion. A category or service of another company is
a 404, covered by a test.

| Verb and URL | Name | Controller@method | Input | Action |
| --- | --- | --- | --- | --- |
| GET `onboarding/services` | `onboarding.services.index` | `ServiceController@index` | | none |
| PUT `onboarding/services` | `onboarding.services.update` | `ServiceController@update` | | `CompleteOnboardingStep`, redirect to step 3 |
| POST `onboarding/services/categories` | `onboarding.services.categories.create` | `Catalogue\CategoryController@create` | | `CreateServiceCategory` with no name |
| PUT `onboarding/services/categories/{category}` | `onboarding.services.categories.update` | `Catalogue\CategoryController@update` | `name[lang]` | `UpdateServiceCategory` |
| DELETE `onboarding/services/categories/{category}` | `onboarding.services.categories.destroy` | `Catalogue\CategoryController@destroy` | | `DestroyServiceCategory(withServices: true)`, snapshot |
| PUT `onboarding/services/categories/{category}/position` | `onboarding.services.categoryPositions.update` | `Catalogue\CategoryPositionController@update` | `direction` in `up`, `down` | `UpdateServiceCategoryPosition` |
| POST `onboarding/services/categories/{category}/services` | `onboarding.services.services.create` | `Catalogue\ServiceController@create` | | `CreateService`, 30 min, no price; redirect opens its panel |
| GET `onboarding/services/services/{service}/edit` | `onboarding.services.services.edit` | `Catalogue\ServiceController@edit` | | none; the panel fragment |
| PUT `onboarding/services/services/{service}` | `onboarding.services.services.update` | `Catalogue\ServiceController@update` | panel form, below | `UpdateService` |
| DELETE `onboarding/services/services/{service}` | `onboarding.services.services.destroy` | `Catalogue\ServiceController@destroy` | | `DestroyService`, snapshot |
| PUT `onboarding/services/services/{service}/duration` | `onboarding.services.serviceDurations.update` | `Catalogue\ServiceDurationController@update` | `duration` | `UpdateServiceDuration` (new, one step services only) |
| PUT `onboarding/services/services/{service}/prices` | `onboarding.services.servicePrices.update` | `Catalogue\ServicePriceController@update` | `prices[currency]` | `UpdateServicePrices` (new) |
| POST `onboarding/services/catalogue` | `onboarding.services.catalogue.create` | `Catalogue\CatalogueController@create` | | `RestoreDefaultCatalogue` |
| DELETE `onboarding/services/catalogue` | `onboarding.services.catalogue.destroy` | `Catalogue\CatalogueController@destroy` | | `DestroyCatalogue`, snapshot |
| POST `onboarding/services/restoration` | `onboarding.services.restoration.create` | `Catalogue\RestorationController@create` | | `RestoreCatalogueSnapshot` |

`{category}` and `{service}` bind by id, as locations do today.

## Panel form (`onboarding.services.services.update`)

| Field | Rule |
| --- | --- |
| `name.{lang}`, `description.{lang}` | nullable string, max 255 and 1000, `lang` a salon language |
| `category` | nullable integer; the action checks it belongs to the company |
| `prices.{currency}` | nullable, `decimal:0,2`, min 0, `currency` a salon currency (R8) |
| `steps` | array, min 1 |
| `steps.*.name.{lang}` | nullable string, max 255 |
| `steps.*.duration` | required integer, min 1, max 1440 |
| `steps.*.requires_staff` | boolean |

A failed rule redirects back with errors; the panel shows them under the field. An action
refusal (`InvalidArgumentException`: duplicate name, no busy step) returns
`back()->withErrors([...])`.

## Fragments named by `x-target`

`catalogue-languages`, `catalogue-summary`, `category-{id}`, `catalogue-list`, `service-panel`,
`catalogue-footer`. Each form names the smallest set it changes.
