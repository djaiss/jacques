# Quickstart: Catalogue Languages

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
| 1 | Sign up as Michael Scott, dismiss the welcome screen, choose Canada | English and French selected, counter reads "2 languages" |
| 2 | Deselect French, then try to deselect English | English stays selected, counter asks to keep at least one |
| 3 | Select Spanish, submit | Company `languages` is `["en", "es"]` |
| 4 | Edit the salon | English and Spanish shown selected |
| 5 | In tinker, `CreateService` with a name in English only | `InvalidArgumentException`: a name is needed in `es` |
| 6 | Create "Women's haircut" / "Corte de mujer", then add French on step 1 | Texts unchanged, `missingLanguages()` returns `["fr"]` |
| 7 | Deselect Spanish on step 1, submit | Dialog names Spanish before saving; after it, the Spanish key is still in the JSON |
| 8 | Same scenarios with the keyboard only and a screen reader | Toggles announce pressed state, flags are not announced |

Spec acceptance scenarios map to the action and feature tests listed in [plan.md](plan.md).
