# Routes: Welcome Screen

Both routes sit in a group with `auth` and `set.locale`, and without `welcome.screen`.

| Verb | URL | Controller | Name | Result |
| --- | --- | --- | --- | --- |
| GET | `welcome` | `WelcomeController@index` | `welcome.index` | 200 with the screen when pending; redirect to `home.index` otherwise |
| PUT | `welcome` | `WelcomeController@update` | `welcome.update` | `DismissWelcomeScreen`, then redirect to `home.index` |

Guests on either route are redirected to `auth.signIn.new` by `auth`.

## Middleware `welcome.screen`

| Request | Result |
| --- | --- |
| guest | passes |
| user, company not pending | passes |
| user, company pending | redirect to `welcome.index` |

Applied to `home.index` and to every `auth` group of `routes/web.php`. Never applied to
`routes/auth.php`.
