# Routes: Clients screen

All three sit in the existing signed in, onboarded group of `routes/web.php`, and only read.

| Verb | URL | Name | Controller | Returns |
| --- | --- | --- | --- | --- |
| GET | `clients` | `clients.index` | `ClientController@index` | All clients |
| GET | `clients/segments/{segment}` | `clientSegments.show` | `ClientSegmentController@show` | Clients in `new`, `regular` or `inactive` |
| GET | `clients/not-rebooked` | `clientsNotRebooked.index` | `ClientNotRebookedController@index` | Clients with no appointment coming up |

Query keys, on every route, each optional:

| Key | Value | Invalid value |
| --- | --- | --- |
| `q` | search text, trimmed | none, any text |
| `location` | id of an active location of the company | 404 |
| `page` | integer from 1 | past the last page: 404 |

- `segment` binds to `ClientSegmentEnum`; any other value answers 404.
- Without `client.view` at any scope, every route answers 404 (R6).
- Tab links keep `q` and `location` and drop `page`; the location picker and the search keep the
  tab and drop `page`. The search form is a GET form, so it works with JavaScript off.

## View model

`App\ViewModels\Clients\ClientsViewModel`, built by the three controllers with
`user`, `segment` (`ClientSegmentEnum|null`), `notRebooked` (bool), `location`, `search`, `page`.

| Method | Gives |
| --- | --- |
| `breadcrumb()` | company name to the dashboard, then Clients |
| `tabs()` | label, icon, count, URL and current state of the five tabs |
| `description()`, `emptyText()` | the sentence of the current tab, the text of an empty list or search |
| `locations()` | the picker options, with the current one |
| `rows()` | for each client of the page: name, phone, salon, last visit date and how long ago, next appointment or null, visit count |
| `footer()` | "12 clients across all salons." through `trans_choice()` |
| `paginator()` | the `LengthAwarePaginator` of R5 |

Menu: `TopBarLayout` passes `clientsUrl` (`route('clients.index')` when `anywhere()` allows
`client.view`), and the Clients tile reads it like `servicesUrl`.
