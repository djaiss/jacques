# Tasks: Company menu

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Each test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Foundational

- [X] T001 In app/View/Components/TopBarLayout.php pass `teamUrl` to the view: `route('settings.team.index')` when `$user->permission(PermissionEnum::RoleManage)->forCompany($user->company)->allowed()`, else `null` (research R3)
- [X] T002 In resources/views/layouts/top-bar.blade.php add `@var string|null $teamUrl` and forward it as `:team-url="$teamUrl"` to `x-top-bar`; in resources/views/components/top-bar.blade.php add the `teamUrl` prop (default `null`) and its `@var`

## Phase 2: User Story 1, open the menu and reach a part of the salon (P1)

Independent test: Michael opens the dashboard, opens the menu, sees the four tiles and the four YOURS links, follows Dashboard and Team.

- [X] T003 [US1] In resources/views/components/top-bar.blade.php make the company button carry `bg-accent-soft` at rest (drop the `:class` toggle and the hover tint), keep `aria-expanded`, `aria-controls` and the chevron rotation
- [X] T004 [US1] Replace `$sections` in top-bar.blade.php with four tiles (label, shortcut, hue, SVG inner markup copied from `Company Menu.dc.html`, url): Dashboard ^1 hue 250 `route('dashboard.index')`, Clients ^2 hue 30 `null`, Services ^3 hue 80 `null`, Team ^4 hue 310 `$teamUrl` (research R1)
- [X] T005 [US1] Redraw the panel in top-bar.blade.php to the design: `top-[70px]`, `w-140 max-w-[92vw]`, hint, input with placeholder `__('Type to go to a client, a service, or a person…')`, grid `grid-cols-2 sm:grid-cols-4 gap-2`, each tile `rounded-[14px] bg-white/8 px-2 pt-6 pb-4.5 gap-3`, shortcut `text-white/60`, 38px icon square styled with `oklch(0.6 0.13 {hue} / 0.24)` background, `inset 0 0 0 1.5px oklch(0.7 0.12 {hue} / 0.32)` ring and `oklch(0.85 0.11 {hue})` colour, 21px SVG `stroke-width="1.8"` `aria-hidden="true"` (research R4)
- [X] T006 [US1] Draw YOURS in top-bar.blade.php as `grid grid-cols-2 gap-0.5` with `mt-5 mb-1.5 pl-3` heading, 7px dot at `bg-white/45`, `hover:bg-white/16`; keep the four existing routes
- [X] T007 [US1] Extend tests/Feature/Controllers/App/DashboardControllerTest.php: Michael sees Dashboard, Clients, Services, Team, the four YOURS labels, the new placeholder, links to `dashboard.index` and `settings.team.index`, and none of People, Assets, Software, Licences, Reports
- [ ] T008 [US1] Create tests/Browser/MenuTest.php: open with the button, close with Escape, open with G, typing G in the search field keeps it open, follow Dashboard

## Phase 3: User Story 2, parts without a screen are already in the menu (P2)

Independent test: activating Clients or Services, or Team without `RoleManage`, changes nothing.

- [X] T009 [US2] In top-bar.blade.php render a tile with a `null` url as `<button type="button" aria-disabled="true">` with the same classes as a linked tile, no hover change and no fading (research R2)
- [X] T010 [US2] Extend DashboardControllerTest.php: Dwight (no `RoleManage`) sees Team without a link to `settings.team.index`; Clients and Services render with `aria-disabled="true"`
- [ ] T011 [US2] Extend tests/Browser/MenuTest.php: clicking Clients keeps the current URL and the panel open

## Phase 4: Polish

- [X] T012 Run `php artisan jacques:localize` and translate every reported string in each lang/*.json locale, then `php artisan jacques:localize --check`
- [X] T013 [P] Run `vendor/bin/pint -q` and `vendor/bin/phpstan analyse --no-progress -q` and fix the findings
- [ ] T014 Run `php artisan test --compact` and the manual scenarios of quickstart.md at 375, 768 and 1280 px in light and dark mode

## Dependencies

- Phase 1 blocks both stories. US2 needs the tile loop of T004 and T005.
- T003 to T006, T009 all edit top-bar.blade.php: do them in order.
- Parallel examples: T007 with T008; T010 with T011; T013 with T012.

## Implementation Strategy

MVP is Phases 1 and 2. US2 is small and ships in the same pull request, since a `null` tile must render as something once T004 lands.
