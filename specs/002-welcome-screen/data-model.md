# Data Model: Welcome Screen

## companies (existing table, one new column)

| Column | Type | Default | Comment |
| --- | --- | --- | --- |
| `is_welcome_screen_pending` | boolean | false | whether the company still has to see the welcome screen shown after signing up |

- Added to `0000_01_01_000000_create_companies_table.php`, after `trial_ends_at`.
- `Company`: add to `$fillable`, cast to `boolean`, document as `@property bool $is_welcome_screen_pending`.

## State transitions

| From | Event | To |
| --- | --- | --- |
| (none) | `CreateCompany` | true |
| (none) | factory, seeder, any other insert | false |
| true | `DismissWelcomeScreen` | false, logs `WelcomeScreenDismissed` |
| false | `DismissWelcomeScreen` | false, nothing logged |

No transition goes back to true.

## UserActionEnum

| Case | Value | Label |
| --- | --- | --- |
| `WelcomeScreenDismissed` | `welcome_screen_dismissed` | Dismissed the welcome screen |
