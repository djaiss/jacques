# Contracts: Catalogue Languages

## PUT onboarding/location: new field

The fields of [004](../../004-salon-onboarding/contracts/routes.md) stay, plus:

| Field | Rules |
| --- | --- |
| `languages` | required, array, min:1 |
| `languages.*` | required, string, distinct, in the keys of `jacques.content_languages` |

The order of `languages` is the order the toggles were selected in. The empty message is
"Choose at least one language."

## Actions

| Action | Change |
| --- | --- |
| `UpdateCompanyLanguages(author, company, languages)` | new; `CompanyManage`; `list<string>`; returns `Company` |
| `SetUpSalon(..., languages)` | new `list<string>` argument, passed to `UpdateCompanyLanguages` |
| `CreateCompany` | writes `languages = ["en"]` |
| `CreateServiceCategory(author, company, name, description)` | `name: array<string, string>`, `description: array<string, ?string>` |
| `UpdateServiceCategory(author, category, name, description)` | same types |
| `CreateService(author, company, name, price, steps, description, category)` | same types for `name` and `description` |
| `UpdateService(author, service, name, price, steps, description, category)` | same types for `name` and `description` |

Each of the four catalogue actions throws `InvalidArgumentException` when:

| Case | Message |
| --- | --- |
| a key is not a company language | `{code} is not a language of the company` |
| a company language has no name, or an empty one | `A name is needed in {code}` |
| a name is taken in that language | `The company already has a service called {name}` (or `service category`) |

`UpdateCompanyLanguages` throws `InvalidArgumentException` for an empty list, an unknown code or a
duplicate. Its log is `UserActionEnum::CompanyLanguagesUpdated`, with the codes as parameter.
