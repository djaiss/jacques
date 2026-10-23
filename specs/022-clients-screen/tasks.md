# Tasks: Clients screen

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

- [X] T001 Run `composer install -q`, `bun install --frozen-lockfile` and `bun run build` in the worktree, then `bunx playwright install chromium`, then `php artisan test --compact` to confirm main is green before any change

## Phase 2: Foundational (one segment rule)

- [X] T002 Add `public static function segmentOf(int $visitCount, ?CarbonInterface $lastVisitAt): ClientSegmentEnum` to app/Helpers/ClientIndicators.php holding the current rule of `segment()` (Inactive when the last visit is older than `INACTIVE_AFTER_MONTHS`, New below `NEW_CLIENT_VISIT_LIMIT` visits, Regular otherwise), and make `segment()` call it (research R2)
- [X] T003 Extend tests/Unit/Helpers/ClientIndicatorsTest.php: `segmentOf()` gives New for 0 visits and no date, New for 1 recent visit, Regular for 3 visits last week, Inactive for 5 visits seven months ago; `segment()` still passes its existing tests

## Phase 3: User Story 1, the salon reads every client (P1)

Independent test: Pam (three visits, one coming up) is listed before Kevin (one visit in March, none booked) when following the Clients tile.

- [X] T004 [US1] Create app/ViewModels/Clients/ClientsViewModel.php with constructor `user`, `segment` (`?ClientSegmentEnum`), `notRebooked` (bool), `location` (`?Location`), `search` (string), `page` (int); a private query of the user's company clients, restricted to clients with an appointment whose `staff_id` is the user's staff when `client.view` is not allowed `forCompany()` (research R6), with subquery columns `visit_count`, `last_visit_at`, `last_visit_id` (status in the `countsAsVisit()` cases, `starts_at <= now()`) and `next_appointment_at` (same statuses, `starts_at > now()`), loaded once and memoized
- [X] T005 [US1] Add to ClientsViewModel `breadcrumb()` (company name to `dashboard.index`, then Clients, like ServicesViewModel), `rows()` sorted by `last_visit_at` descending with clients without a visit last, each row `name` (first and last name, "Erased client" when `anonymized_at` is set), `phone`, `salon` (location of the last visit, loaded for the page rows in one query), `lastVisit` (`isoFormat('ll')` in that location's time zone) and `lastVisitAgo` (`diffForHumans()`), or "No visit yet", `nextAppointment` (date or null, shown as "None booked"), `visitCount`; and `footer()` with `trans_choice(':count client across all salons.|:count clients across all salons.', …)`
- [X] T006 [US1] Write tests/Unit/ViewModels/Clients/ClientsViewModelTest.php: Angela, Pam, Kevin, Dwight sorted as quickstart scenario 1; cancelled and no show appointments never count; a client of Vance Refrigeration never appears; Kelly with self scope sees only Pam; an account without staff on self scope sees nobody; an erased client reads "Erased client"
- [X] T007 [US1] Create app/Http/Controllers/App/Clients/ClientController.php `index(Request)`: `$request->user()->permission(PermissionEnum::ClientView)->anywhere()->authorize()`, then render `app.clients.index` with ClientsViewModel built from `q` (trimmed), `location` and `page`; add `Route::get('clients', [ClientController::class, 'index'])->name('clients.index')` in the signed in, onboarded group of routes/web.php
- [X] T008 [US1] Create resources/views/app/clients/index.blade.php and _row.blade.php: `x-top-bar-layout` titled Clients, `x-breadcrumb`, title, the lead "Everybody who has booked at one of your salons. A client belongs to the account, so the same person can be seen in several salons.", the six column list (Client with `x-avatar-initials` tone accent, name truncated with a `title`, phone; Salon; Last visit with how long ago; Next appointment with a dot and its text, never the colour alone; Visits right aligned), stacked on a phone, rows not links (research R8), the empty text "Clients appear here after their first booking.", the footer; no "Add a client" button
- [X] T009 [US1] Pass `clientsUrl` from app/View/Components/TopBarLayout.php (`route('clients.index')` when `ClientView` is allowed `anywhere()`), declare it in resources/views/components/top-bar.blade.php and use it as the Clients tile `url`
- [X] T010 [US1] Write tests/Feature/Controllers/App/Clients/ClientControllerTest.php: Michael (owner) gets 200 and sees Pam and Kevin; a user without `client.view` gets 404; a client of another company is absent
- [X] T011 [US1] Update tests/Browser/MenuTest.php: the inert tile test signs in a user without `client.view` and without the Services permission (the owner's Clients tile now leads somewhere); add a test following Clients from the menu to `/clients`

## Phase 4: User Story 2, the salon finds one client (P1)

Independent test: "angela", "(570) 555-0100" and "schrute" each find one client; Nashua shows only Kevin.

- [X] T012 [US2] Add search to ClientsViewModel (research R3): name and email compared after `Str::ascii()` and `mb_strtolower()`; the phone matched when the query has at least three digits, leading zeros dropped, contained in the stored digits; an erased client never matches
- [X] T013 [US2] Add the location filter to ClientsViewModel: `locations()` lists active locations (`archived_at` null) with the current one; a set `location` keeps clients with any appointment at it (one `pluck('client_id')` query); `footer()` and `emptyText()` say "at :salon" instead of "across all salons"; the search empty text is "Nobody matches “:search” across all salons." or "… at :salon."
- [X] T014 [US2] In app/Http/Controllers/App/Clients/ClientController.php resolve `location` with `$request->user()->company->locations()->whereNull('archived_at')->findOrFail()` when given
- [X] T015 [US2] Add to index.blade.php the GET form with the search field (`type="search"`, label "Search by name, phone or email", name `q`) and the salon `x-dropdown` or native select (name `location`, "All salons" first) submitted on change and by its button with JavaScript off; both keep the current tab and drop `page`
- [X] T016 [US2] Extend ClientsViewModelTest.php with the searches of spec US2 (case, accents with "Oscar Martínez" found by "martinez", phone formats, two digits not searching the phone) and the location filter; extend ClientControllerTest.php: an archived or another company's location answers 404

## Phase 5: User Story 3, segments (P2)

Independent test: on quickstart data the tabs read All 4, New 2, Regular 1, Inactive 1, Not rebooked 3.

- [X] T017 [US3] Add to ClientsViewModel the segment of each row through `ClientIndicators::segmentOf()`, the filter on `segment` or `notRebooked` (no `next_appointment_at`), `tabs()` with label, icon, count (under the current search and location, through `trans_choice('[0,*]:count', …)`), URL keeping `q` and `location`, and `isCurrent`; `description()` and `emptyText()` per tab from the spec US3 table
- [X] T018 [US3] Create app/Http/Controllers/App/Clients/ClientSegmentController.php `show(Request, ClientSegmentEnum $segment)` and app/Http/Controllers/App/Clients/ClientNotRebookedController.php `index(Request)`, authorized and built like ClientController; add `clients/segments/{segment}` (`clientSegments.show`) and `clients/not-rebooked` (`clientsNotRebooked.index`) to routes/web.php, before any future `clients/{client}` route
- [X] T019 [US3] Add the tab bar to index.blade.php: links with icon, label and count, `aria-current="page"` on the current one, wrapping on a phone, and the description line under it
- [X] T020 [US3] Extend ClientsViewModelTest.php with the counts and rows of each tab on quickstart data, counts following the search; write tests/Feature/Controllers/App/Clients/ClientSegmentControllerTest.php (Inactive lists Kevin, an unknown segment is 404, no `client.view` is 404) and ClientNotRebookedControllerTest.php

## Phase 6: Polish

- [X] T021 Paginate in ClientsViewModel by 50 with a `LengthAwarePaginator` (`paginator()`), previous and next links with "Page :page of :pages" in index.blade.php, a page past the last answering 404 from the three controllers; test it in ClientControllerTest.php with 51 clients
- [X] T022 Write tests/Browser/ClientsTest.php: follow the menu, search, pick a salon, switch tabs, no JavaScript errors, no horizontal scroll at 375 px
- [X] T023 Run `php artisan jacques:localize` and translate every reported string in each lang/*.json locale, then `php artisan jacques:localize --check`
- [X] T024 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [ ] T025 Run `php artisan test --compact` and the manual walk of quickstart.md at 375, 768 and 1280 px, keyboard only and zoomed to 200%

## Dependencies

- T001 before everything; T002 and T003 block T017.
- US1 (T004 to T011) before US2 and US3: both extend its view model, controller and view.
- US2 and US3 touch the same files (ClientsViewModel.php, index.blade.php, routes/web.php and the tests); work them one story at a time.
- Parallel examples: T003 with T004; T009 with T008; T011 with T010; T024 with T023.

## Implementation Strategy

MVP is US1: the menu leads to the full list, scoped and sorted. US2 makes it usable at volume, US3
adds follow up. Each phase ends green; pagination (T021) lands before the pull request.
