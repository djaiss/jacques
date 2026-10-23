# Routes: Dashboard agenda

All three sit in the existing signed in, onboarded group of `routes/web.php`.

| Verb | URL | Name | Controller | Returns |
| --- | --- | --- | --- | --- |
| GET | `dashboard` | `dashboard.index` (exists) | `DashboardController@index` | The agenda for today in the salon time zone |
| GET | `dashboard/{date}` | `dashboard.show` | `DashboardController@show` | The agenda for `date` |
| GET | `dashboard/{date}/calendar/{month}` | `dashboard.calendar.show` | `DashboardCalendarController@show` | The calendar of `month` with `date` selected, `id="calendar"` |

- `date` matches `\d{4}-\d{2}-\d{2}`, `month` matches `\d{4}-\d{2}`. A value that matches but is
  not a real date (`2026-02-30`, `2026-13`) answers 404.
- `dashboard/{today}` answers the same page as `dashboard`; it does not redirect.
- The calendar route answers the full page when called without Alpine Ajax, so the month arrows
  still work with JavaScript off.
- No route writes anything; bookings and cancellations never reach the server.
