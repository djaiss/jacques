# Quickstart: Screenshot Sign In Link

## Prerequisites

- Herd serves the project on `http://jacques.test`, and `.env` has `APP_URL=http://jacques.test`.
- The instance is seeded: `php artisan migrate:fresh --seed --quiet`.
- `.env` has `JACQUES_SCREENSHOT_LINK_ENABLED=true` and `DEBUGBAR_ENABLED=false`.

## Scenario 1: capture a screen behind the sign in

```bash
php artisan jacques:screenshot-link michael.scott@dundermifflin.com --to=/settings/administration/locations
```

Expected: one link, then a line naming Michael Scott and five minutes.

```bash
iris --scale 1 --size 1440x1000 '<the link>' -o /tmp/locations.png
```

Expected: the image shows the locations screen, signed in as Michael Scott.

## Scenario 2: refusals

| Change | Expected |
| --- | --- |
| `JACQUES_SCREENSHOT_LINK_ENABLED=false` | Exit 1, names the setting |
| `APP_URL=https://dundermifflin.com` | Exit 1, names `dundermifflin.com` |
| Address `toby.flenderson@dundermifflin.com` | Exit 1, names the address |

## Scenario 3: altered and expired links

Replace `michael.scott` with `dwight.schrute` in a minted link and open it: `403`. Open a link
older than five minutes: `403`.

## Automated tests

```bash
php artisan test --compact --filter=ScreenshotLink
```

## Friends references

```bash
grep -rniE "geller|chandler|phoebe|buffay|tribbiani|central perk" tests database .claude/skills
```

Expected: no output.
