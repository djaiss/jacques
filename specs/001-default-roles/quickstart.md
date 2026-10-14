# Quickstart: Default Roles

## Setup

```bash
php artisan migrate:fresh --seed --quiet
```

## Automated checks

```bash
php artisan test --compact
```

```bash
php artisan jacques:localize --check
```

```bash
vendor/bin/pint --test -q && vendor/bin/phpstan analyse --no-progress -q
```

## Manual scenarios

| # | Steps | Expected |
| --- | --- | --- |
| 1 | Sign up a new salon, open Settings, then Roles and permissions | Owner, Manager, Staff, Receptionist; you hold Owner; Owner shows "Not editable" |
| 2 | Open Manager, change one scope to "Their locations", save | Saved; the scope picker offers only the scopes the permission allows |
| 3 | Create two locations A and B, assign a colleague to A, give them Manager | They can edit A and get a 404 on B |
| 4 | As that Manager, open Roles and permissions | Not offered; the URL answers 404 |
| 5 | Try to take Owner away from yourself while you are the only Owner | Refused with an explanation |
| 6 | Give Owner to the colleague, then take it away from yourself | Both succeed |

Data model and grants: [data-model.md](data-model.md).
