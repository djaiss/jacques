# Quickstart: Company menu

## Prerequisites

`php artisan migrate:fresh --seed --quiet`, `npm run build --silent`, then sign in at
`https://jacques.test` as the owner of Dunder Mifflin, and in a second browser as one of the five
other staff members.

## Automated

```bash
php artisan test --compact --filter=DashboardControllerTest
php artisan test --compact tests/Browser/MenuTest.php
php artisan jacques:localize --check
```

## Manual scenarios

| # | Who | Do | Expect |
| --- | --- | --- | --- |
| 1 | Owner | Open the dashboard, press the company button | Panel as in the design: hint, search, Dashboard ^1, Clients ^2, Services ^3, Team ^4, YOURS on two columns |
| 2 | Owner | Follow Team | Team members screen |
| 3 | Owner | Activate Clients, then Services | Nothing changes, address unchanged |
| 4 | Staff | Open the panel, activate Team | Team drawn, nothing happens |
| 5 | Any | Press G outside a field, then Escape; click outside | Opens, closes, closes |
| 6 | Any | Type G in the search field | The letter is typed, the panel stays |
| 7 | Any | Repeat 1 at 375, 768 and 1280 pixels, light and dark | No horizontal scroll; tiles on two columns under 640 pixels |
| 8 | Any | Keyboard only, then with a screen reader | Focus stays in the panel; menu state and inert tiles announced |
