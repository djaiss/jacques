# Contract: Screenshot Sign In Link

## Command

```text
php artisan jacques:screenshot-link {email} {--to=/}
```

| Input | Meaning |
| --- | --- |
| `email` | Address of the account to sign in as. Matched in lower case. |
| `--to` | Path the link lands on. Defaults to `/`. |

| Condition, checked in this order | Exit | Output |
| --- | --- | --- |
| Not available (setting off, or production) | 1 | Names `JACQUES_SCREENSHOT_LINK_ENABLED` and says it only works outside production. |
| Host of `APP_URL` is not a development host | 1 | Names the host. |
| No account uses the address | 1 | Names the address. |
| Otherwise | 0 | The link on its own line, then the account and the lifetime in minutes. |

## Route

```text
GET screenshot-link?email=…&to=…&expires=…&signature=…
name: auth.screenshotLink.show
middleware: signed, set.locale
```

| Condition, checked in this order | Response |
| --- | --- |
| Signature invalid or expired | `403` (the `signed` middleware) |
| Not available, or request host not a development host | `404` |
| No account uses the address | `404` |
| Otherwise | Signs in, regenerates the session, redirects to `to` when it is a local path, `/` otherwise |

## Configuration (`config/jacques.php`)

| Key | Environment variable | Default |
| --- | --- | --- |
| `screenshot_link_enabled` | `JACQUES_SCREENSHOT_LINK_ENABLED` | `false` |
| `screenshot_link_duration_minutes` | `JACQUES_SCREENSHOT_LINK_DURATION_MINUTES` | `5` |

Development hosts: research R5. Local path rule: research R6.
