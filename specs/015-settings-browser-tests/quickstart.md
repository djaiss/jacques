# Quickstart: Settings Browser Tests

## Prerequisites

Built assets and Chromium, once per machine:

```bash
bun run build
bunx playwright install chromium
```

## Run

```bash
vendor/bin/pest --testsuite=Browser
```

One file, while writing it:

```bash
vendor/bin/pest tests/Browser/Settings/Account/ProfileTest.php
```

Debug a failing test in a visible browser:

```bash
vendor/bin/pest tests/Browser/Settings --headed
```

## Validation

| Check | Expected |
| --- | --- |
| Full `Browser` suite, three runs in a row | all green each time (SC-004) |
| Duration against the 3.5 s baseline of the 6 auth tests | under 3 minutes more (SC-003) |
| Every screen of the plan table has a file | 11 files under `tests/Browser/Settings` (SC-001) |
| Remove `x-on:click` from the confirming button of `_delete-role.blade.php` locally | at least one test fails (SC-002), then revert |
| `grep -rlE '^(final \|abstract )?class ' tests/Browser` | no output |
| `vendor/bin/pint --test` and `vendor/bin/phpstan analyse` | clean |
| Browser tests workflow on the pull request | green |
