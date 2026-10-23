# Quickstart: Clients screen

## Prerequisites

The worktree has no `vendor/` nor `node_modules/` yet.

```bash
composer install -q && bun install --frozen-lockfile && bun run build && bunx playwright install chromium
```

## Checks

```bash
php artisan test --compact --filter=Client
php artisan jacques:localize --check
vendor/bin/pint --dirty
vendor/bin/phpstan analyse --no-progress
```

## Scenarios

Seed a Dunder Mifflin company with two locations (Scranton, Nashua) and these clients, then sign
in as Michael (owner).

| Client | Visits | Next appointment |
| --- | --- | --- |
| Pam Beesly | 3, last one last week at Scranton | in 5 days |
| Kevin Malone | 1, seven months ago at Nashua | none |
| Angela Martin, +1 570 555 0100 | 1, yesterday at Scranton | none |
| Dwight Schrute | 0 (one cancelled) | none |

1. Open the menu, follow Clients: rows read Angela, Pam, Kevin, then Dwight with "No visit yet".
2. Tabs read All 4, New 2 (Angela, Dwight), Regular 1, Inactive 1, Not rebooked 3.
3. Search "555 0100", then "(570) 555-0100": Angela only. Search "schrute": Dwight only.
4. Pick Nashua: Kevin only, footer "1 client at Nashua.". Open Inactive: the address keeps Nashua.
5. Search "xyz": "Nobody matches “xyz” across all salons."
6. Sign in as Kelly with `client.view` on self and one appointment for Pam: only Pam is listed.
7. Sign in as somebody without `client.view`: the tile is inert, `clients` answers 404.
8. At 375 px wide, the toolbar and tabs wrap and no row scrolls the page sideways; with the keyboard
   alone, reach the search, the picker, each tab and the page links.

Contracts: [routes.md](contracts/routes.md).
