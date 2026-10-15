# Contracts: Salon Currencies

## PUT onboarding/location: new field

The fields of [004](../../004-salon-onboarding/contracts/routes.md) and
[005](../../005-catalogue-languages/contracts/actions.md) stay, plus:

| Field | Rules |
| --- | --- |
| `currencies` | required, array, min:1 |
| `currencies.*` | required, string, distinct, in `jacques.currencies` |

The order of `currencies` is the order the toggles were selected in. The empty message is
"Choose at least one currency."

## Actions

| Action | Change |
| --- | --- |
| `UpdateCompanyCurrencies(author, company, currencies)` | new; `CompanyManage`; `list<string>`; returns `Company` |
| `SetUpSalon(..., currencies)` | new `list<string>` argument, passed to `UpdateCompanyCurrencies` |
| `UpdateCompany` | `currency` argument removed |
| `CreateCompany` | writes `currencies = ["CAD"]` |
| `CreateService(author, company, name, prices, steps, description, category)` | `prices: array<string, int>` replaces `price: int` |
| `UpdateService(author, service, name, prices, steps, description, category)` | same |
| `CreateLocationService(author, location, service, prices)` | `prices: array<string, int>`, default `[]` |
| `UpdateLocationService(author, locationService, prices, isActive)` | same |
| `CreateDefaultCatalogue` | writes `prices = ['CAD' => amount]` |

The four price actions throw `InvalidArgumentException` when:

| Case | Message |
| --- | --- |
| a key is not a company currency | `{code} is not a currency of the company` |
| an amount is negative | `A price cannot be negative` |
| a company currency has no price (services only) | `A price is needed in {code}` |

`UpdateCompanyCurrencies` throws `InvalidArgumentException` for an empty list, an unknown code or
a duplicate. Its log is `UserActionEnum::CompanyCurrenciesUpdated`, with the codes as parameter.
