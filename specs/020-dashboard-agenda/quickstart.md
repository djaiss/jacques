# Quickstart: Dashboard agenda

## Prerequisites

`php artisan migrate:fresh --seed --quiet`, `npm run build --silent`, then sign in at
`https://jacques.test` as the owner of Dunder Mifflin.

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Dashboard
php artisan test --compact --filter=Dashboard tests/Feature
php artisan test --compact tests/Browser/DashboardTest.php
php artisan jacques:localize --check
```

## Manual scenarios

| # | Do | Expect |
| --- | --- | --- |
| 1 | Open the dashboard | Today selected and outlined in the calendar, four columns (Alexandra unchecked), red time line during 9:00 to 19:00 |
| 2 | Reload, then open the same day from the calendar | Identical appointments |
| 3 | Press the next day arrow until a Sunday | Closed salon message, no grid |
| 4 | Page the calendar two months ahead, pick a day | The day shows; the address is `dashboard/{that date}` |
| 5 | Uncheck every hairdresser, then check All | Empty message, then five columns and All checked |
| 6 | Set the slot size to 15, then 30 | Faint lines follow the size |
| 7 | Press a block, Cancel this appointment, then Keep it, then Cancel it | Warning shows; only Cancel it removes the block |
| 8 | Press a free slot, leave the name empty, then type one and add | Add disabled, then the block appears at that time |
| 9 | Reload | Booked block gone, cancelled block back |
| 10 | Repeat 1 at 375, 768 and 1280 pixels, light and dark | Left column stacks on a phone; grid scrolls inside its area; headers stay visible |
| 11 | Keyboard only, then a screen reader | Every slot, block and checkbox reachable; panel traps focus and closes with Escape |
| 12 | Switch the account to French | Copy matches the design; dates and counts in French |
