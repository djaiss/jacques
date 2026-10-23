# Research: Clients screen

## R1. One query for the rows, the rest in PHP

- **Decision**: one query loads every client the viewer may see, with three subquery columns:
  `visit_count`, `last_visit_at` and `next_appointment_at`, plus `last_visit_id`. Search, the
  location filter, segments, tab counts, sorting and the page slice then run on that collection.
  A second query loads the last visit's location for the 50 rows of the page.
- **Rationale**: tab counts need every row anyway; a company has at most a few thousand clients
  (10,000 in SC-002), which hydrates well under a second; no driver specific SQL is needed.
- **Alternatives**: query scopes per segment with SQL pagination (five counting queries and the
  segment rule written twice, in SQL and in PHP); `ClientIndicators` per client (one query per
  client).

## R2. The segment rule stays in one place

- **Decision**: `ClientIndicators::segment()` delegates to a new static
  `ClientIndicators::segmentOf(int $visitCount, ?CarbonInterface $lastVisitAt)`, which the list
  calls for each row. A visit is the existing rule: `countsAsVisit()` and started.
- **Rationale**: FR-003; the list and the client file can never disagree (SC-004).
- **Alternatives**: a copy of the thresholds in the view model.

## R3. Search folds accents in PHP

- **Decision**: name and email are compared after `Str::ascii()` and `mb_strtolower()`, like the
  services search. The phone is compared on digits: the query's digits, leading zeros dropped,
  must appear in the stored E.164 digits; fewer than three digits do not search the phone.
- **Rationale**: accent folding has no portable SQL form; dropping the trunk zero lets
  "06 12 34" find "+33612345678".
- **Alternatives**: a normalized search column (needs upkeep in every action writing a client,
  none of which exists yet); `whereLike` (folds case, not accents).

## R4. Addresses

- **Decision**: `clients` for All clients, `clients/segments/{segment}` bound to
  `ClientSegmentEnum` for New, Regular and Inactive, `clients/not-rebooked` for the fifth tab.
  Location, search and page are the query keys `location`, `q` and `page`.
- **Rationale**: tabs are places, as on the team screen; filters combine with every tab, so query
  keys are the good reason the code style asks for. `clients/new` stays free for the creation form.
- **Alternatives**: `clients/{tab}` (collides with `clients/new`); a path per location (one route
  per tab and location pair).

## R5. Pagination

- **Decision**: a `LengthAwarePaginator` built from the sliced collection, rendered as previous
  and next links with "Page 2 of 7". A page past the last answers 404.
- **Alternatives**: page numbers (more markup, no need at this volume).

## R6. Scope of `client.view`

- **Decision**: `forCompany()` allowed shows every client; otherwise `anywhere()` allowed shows
  the clients of an appointment whose `staff_id` is the viewer's staff; otherwise 404. The menu
  tile uses `anywhere()`, like Services.
- **Rationale**: the self scope of appointments in specs/019; an account without staff sees none.

## R7. Copy and breadcrumb

- **Decision**: the copy says "salon" for a location, as the services screen does. The breadcrumb
  is company name › Clients, the existing convention, instead of the design's "Dashboard".
- **Rationale**: the code next to the screen wins over the design (CLAUDE.md order).

## R8. Rows are not links

- **Decision**: no hover state, no pointer cursor, no link on a row until the client file exists
  (FR-010). The avatar uses `x-avatar-initials` with its accent tone; it has no hue, so rows share one tint.

## R9. API

- **Decision**: no API endpoint in this feature; a justified gap under constitution VII, as
  previous screens did. The list logic sits in the view model, which an API resource can mirror.
