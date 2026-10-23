# Research: Dashboard agenda

## R1. Where the generated content lives

- **Decision**: `app/ViewModels/Dashboard/GeneratedAgenda`, a plain class returning the five
  hairdressers, the service list and, for a hairdresser and a date, the day plan. It is replaced by
  real queries when appointments exist, and nothing outside `Dashboard` reads it.
- **Rationale**: it only feeds one screen and writes nothing; a view model folder is where screen
  data is assembled. No new top level folder.
- **Alternatives considered**: a seeder and real tables (the spec asks for no data); generating in
  JavaScript as the design does (untestable in Pest, logic in the view).

## R2. Same day, same content

- **Decision**: one `Random\Randomizer` per hairdresser and day, on `new Mt19937(crc32("{id}{Y-m-d}"))`,
  following the design's rules: 10% leave, start 9:00 or 10:00, end 18:00 or 19:00, lunch at 12:30
  or 13:00 for an hour, appointments packed with random gaps and never across lunch or the end.
- **Rationale**: no global seed is touched, so other code stays random; the output is stable
  across requests and PHP versions.
- **Alternatives considered**: `mt_srand()` (global state); caching a generated week (storage for a mock).

## R3. How a day is addressed

- **Decision**: `GET dashboard` shows today; `GET dashboard/{date}` with `date` as `Y-m-d` shows
  that day. Arrows, Today and calendar days are plain links. Month arrows call
  `GET dashboard/{date}/calendar/{month}` with `x-target="calendar"`, so the shown day stays.
- **Rationale**: a day is shareable and reloads to itself; the code style forbids query strings
  without reason. Month paging without changing the day needs its own small endpoint.
- **Alternatives considered**: `?date=` (query string); a month grid computed in Alpine (duplicates
  date logic and its localization in JavaScript).

## R4. What stays on the client

- **Decision**: one Alpine component on the dashboard holds `visible` (hairdresser ids), `slot`
  (15, 20, 30), `panel`, `draft`, `added` and `removed`. Every column is rendered by the server and
  hidden with `x-show`; the day total is summed from the visible columns. Added blocks are drawn
  with `x-for` using the same `top = (start - 540) * 1.5` rule the server uses.
- **Rationale**: these choices are not remembered (spec assumption), so a round trip buys nothing.
- **Alternatives considered**: a request per checkbox (slower, and the state would need a URL).

## R5. Pressing a free slot

- **Decision**: each free slot of the chosen size is a transparent `<button>` in the column with an
  `aria-label` such as "Book at 10:20 with Amélie", built in Alpine from the working ranges the
  server passes. Hatched time has no button.
- **Rationale**: the views skill forbids a clickable `<div>`; the design reads the click height,
  which a keyboard cannot reach.

## R6. Today and the current time

- **Decision**: today and now are taken in the time zone of the company's first location, falling
  back to `config('app.timezone')`. Alpine moves the line every 30 seconds from a server offset.
- **Rationale**: invariant D2. The design's fixed 12:30 was only for its preview.

## R7. Differences with the design kept on purpose

| Design | App | Why |
| --- | --- | --- |
| French copy | English source, French translation | Every string through `jacques:localize` |
| `oklch(0.95 0.035 hue)` blocks | Same, plus a dark variant | Dark mode |
| Left column 272 pixels | Stacks above the agenda under `lg` | Phone (constitution) |
| Own top bar | Existing `x-top-bar-layout` | Spec assumption |
| Hours `9:00` | `isoFormat('LT')` | Locale (FR-009) |
