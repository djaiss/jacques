# Quickstart: Pest Migration

Validation guide for the supervisor, each batch and the reviewer. Run from the repository root. `before.xml` and `after.xml` are working files and are never committed.

## 1. Baseline, once, on `main`

```bash
git switch -q main && php artisan test --parallel --log-junit before.xml && git switch -q 2026-10-16-pest-migration
```

Expected on 2026-10-16: 1143 tests, 2954 assertions, all passing.

## 2. A batch

```bash
vendor/bin/pest --parallel --log-junit after.xml tests/Unit/Enums
```

Then compare. Only the files of the batch matter, so differences elsewhere are ignored until the end.

```bash
php specs/014-pest-migration/compare-inventory.php before.xml after.xml
```

## 3. Greps that must print nothing

```bash
grep -rlE '^(final |abstract )?class ' tests/Unit tests/Feature
```

```bash
grep -rnE 'PHPUnit\\Framework|#\[Test\]|#\[DataProvider' tests/Unit tests/Feature
```

```bash
grep -rnE '\$this->(assert(Equals|Same|NotEquals|NotSame|True|False|Null|NotNull|Count|InstanceOf|NotEmpty|String|Contains|NotContains|ArrayHasKey|ArrayNotHasKey|GreaterThan|Is)|expectException)' tests/Unit tests/Feature
```

A file level helper holding `$this` fails at run time, so the batch's green run covers R3.

## 4. End of the migration

| Check | Command | Expected |
| --- | --- | --- |
| Inventory | `php specs/014-pest-migration/compare-inventory.php before.xml after.xml` | one line, `tests/Unit/ExampleTest.php: tests 1 -> 0` |
| Duration | `php artisan test --parallel`, three times | median at most 4.2 s |
| A failure is reported | turn one `toBe` false on purpose, run, revert | non zero exit code |
| Style | `vendor/bin/pint --test` | clean |
| Analysis | `vendor/bin/phpstan analyse --memory-limit=1G` | nothing new |
| Untouched code | `git diff --stat main -- app routes database resources` | empty |
| CI | the pull request | SQLite, MySQL, PostgreSQL green |
