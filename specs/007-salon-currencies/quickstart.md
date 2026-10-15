# Quickstart: Salon Currencies

## Prerequisites

```bash
php artisan migrate:fresh --seed -q
```

## Automated checks

```bash
php artisan test --compact
```

```bash
vendor/bin/phpstan analyse --no-progress -q
```

```bash
php artisan jacques:localize --check
```

## Manual scenarios

| # | Do | Expect |
| --- | --- | --- |
| 1 | Sign up as Michael Scott, dismiss the welcome screen, choose Canada | Canadian dollar selected alone, counter reads "1 currency. Keep at least one." |
| 2 | Choose France | Euro selected alone |
| 3 | Choose Canada, add US dollar, submit | Company `currencies` is `["CAD", "USD"]` |
| 4 | Edit the salon | Canadian dollar and US dollar shown selected |
| 5 | In tinker, `missingCurrencies()` on a default service | `["USD"]`; its `prices` is still `{"CAD": ...}` |
| 6 | In tinker, `CreateService` with a CAD price only | `InvalidArgumentException`: a price is needed in `USD` |
| 7 | `UpdateLocationService` with `['USD' => 4800]`, then `resolvedPrice('CAD')` | The service's CAD price |
| 8 | Deselect US dollar on step 1, submit | Dialog names US dollar before saving; after it, the `USD` keys are still in the JSON |
| 9 | Same scenarios with the keyboard only and a screen reader | Toggles announce pressed state, each reads its name and code |

Spec acceptance scenarios map to the action and feature tests listed in [plan.md](plan.md).
