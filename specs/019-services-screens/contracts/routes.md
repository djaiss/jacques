# Routes: Services screens

All behind `auth`, the onboarding guards the other app screens use, and the checks of research R1.
A record of another company returns 404. Numeric parameters are constrained with `whereNumber`.

| Verb | URL | Name | Controller@method | Permission |
| --- | --- | --- | --- | --- |
| GET | `services` | `services.index` | ServiceController@index | view |
| GET | `services/locations/{location}` | `serviceLocations.index` | ServiceLocationController@index | view |
| GET | `services/new` | `services.new` | ServiceController@new | manage |
| GET | `service-categories/{category}/services/new` | `categoryServices.new` | CategoryServiceController@new | manage |
| POST | `services` | `services.create` | ServiceController@create | manage |
| GET | `services/{service}` | `services.show` | ServiceController@show | view |
| GET | `services/{service}/edit` | `services.edit` | ServiceController@edit | manage |
| PUT | `services/{service}` | `services.update` | ServiceController@update | manage |
| POST | `services/{service}/archive` | `serviceArchives.create` | ServiceArchiveController@create | manage |
| DELETE | `services/{service}/archive` | `serviceArchives.destroy` | ServiceArchiveController@destroy | manage |
| GET | `service-categories/new` | `serviceCategories.new` | ServiceCategoryController@new | manage |
| POST | `service-categories` | `serviceCategories.create` | ServiceCategoryController@create | manage |
| GET | `service-categories/{category}/edit` | `serviceCategories.edit` | ServiceCategoryController@edit | manage |
| PUT | `service-categories/{category}` | `serviceCategories.update` | ServiceCategoryController@update | manage |
| DELETE | `service-categories/{category}` | `serviceCategories.destroy` | ServiceCategoryController@destroy | manage |

`services.index` and `serviceLocations.index` read an optional `q` (search).

## Redirects and messages

| After | Goes to | Status message |
| --- | --- | --- |
| create service | `services.show` | "The service is added." |
| update service | `services.show` | "The service is saved." |
| archive | `services.index` | "The service is archived." |
| restore | `services.show` | "The service is back in the catalog." |
| create, update category | `services.index` | "The category is added." / "The category is saved." |
| destroy category | `services.index` | "The category is removed." |

A validation failure returns to the form with its values and the errors next to the fields.
