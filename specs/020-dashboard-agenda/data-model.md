# Data model: Dashboard agenda

Nothing is stored. These are the shapes `GeneratedAgenda` returns and the view models expose.

## Generated

| Shape | Fields |
| --- | --- |
| Hairdresser | `id` (amelie, sammy, charlotte, alexandra, lucas), `name`, `hue` (310, 250, 200, 80, 30), `visibleByDefault` (false for alexandra) |
| Service | `name` (translated), `duration` in minutes: women's cut 45, men's cut 30, blow dry 30, colour 90, highlights 120, beard trim 20, child's cut 20, deep treatment 30 |
| DayPlan | `absent`, `start`, `end`, `lunchStart` (minutes from midnight), `appointments` |
| Appointment | `id` (hairdresser id, date, index), `start`, `duration`, `client`, `service` |

Rules: a Sunday has no plan; an absent hairdresser has no appointment; an appointment lies inside
`[start, end]` and outside `[lunchStart, lunchStart + 60]`.

## Exposed to the view

| Block | Gives |
| --- | --- |
| `DashboardViewModel` | `title()`, `isClosed()`, `calendar()`, `day()`, `hairdressers()`, `services()`, `previousDayUrl()`, `nextDayUrl()`, `todayUrl()`, `alpineState()` |
| `MonthCalendar` | `label()`, `weekdayInitials()`, `weeks()` of days (`number`, `url`, `inMonth`, `isSelected`, `isToday`, `isSunday`), `previousUrl()`, `nextUrl()` |
| `DayAgenda` | `hours()` (`label`, `top`), `height()`, `columns()`: `id`, `name`, `hue`, `countLabel`, `dayItems`, `offBlocks` (`top`, `height`, `label`), `appointments` (`top`, `height`, `compact`, `time`, `client`, `service`), `workingRanges` |

Geometry: 1.5 pixels per minute from 9:00, so the grid is 900 pixels high and an appointment is
`duration * 1.5 - 2` high; `compact` when that is under 44.
