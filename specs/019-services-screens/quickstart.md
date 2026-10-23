# Quickstart: Services screens

## Prerequisites

`php artisan migrate:fresh --seed --quiet`, `bun run build`, sign in at `https://jacques.test` as
the owner, finish onboarding with the example catalogue and two languages (English, French), then
add the locations Stamford and Nashua and put a few people in each.

## Automated

```bash
vendor/bin/pest --parallel --compact --testsuite=Unit,Feature
vendor/bin/pest --compact tests/Browser/Services
php artisan jacques:localize --check
```

## Manual scenarios

| # | Who | Do | Expect |
| --- | --- | --- | --- |
| 1 | Owner | Menu, Services | List with lead, navigation, one card per category, warnings where nobody does a service |
| 2 | Owner | Search "balay", then "zzz" | Only Balayage; then the no result card |
| 3 | Owner | Pick Stamford, open a row, go back | Lead and people heading for Stamford; back keeps Stamford |
| 4 | Owner | Add "Beard Trim" from the Haircuts card, Scranton with Jim | Page of the service; Jim under Scranton |
| 5 | Owner | Edit it, switch to French, name "Taille de barbe", untick Scranton | Saved; French name shown in French; "Not offered anywhere yet" |
| 6 | Owner | Archive it, then restore it from its page | Leaves the list; comes back unchanged |
| 7 | Owner | Add category "Barbering", rename it, remove it with a service in it | Service moves to "Without a category" |
| 8 | Staff without the change permission | Open list and a service; open an edit address | No add, edit or archive control; 404 |
| 9 | Any | 375, 768, 1280 pixels, light and dark, keyboard only, screen reader | No horizontal scroll; sample figures note read out; warnings in words |
