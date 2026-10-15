# Tasks: Add Favicon

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by FR-004 and constitution principle III. The test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

No setup: ImageMagick (`magick`) is already on the developer machine, and no package is added.

## Phase 2: Foundational

- [X] T001 Replace the empty public/favicon.ico with `magick specs/011-add-favicon/favicon-source.png public/favicon.ico`, and confirm `file public/favicon.ico` reports one 32 by 32 icon (research R1, R2)

## Phase 3: User Story 1 - Recognize Jacques among open tabs (P1) MVP

- [X] T002 [US1] Add `<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32" />` after the `<title>` in resources/views/welcome.blade.php, matching resources/views/partials/meta.blade.php (research R3)
- [X] T003 [P] [US1] Create tests/Feature/FaviconTest.php: one test that public/favicon.ico exists, is not empty and starts with the bytes `00 00 01 00`; one that a guest GET of `route('auth.signIn.new')` and of `/` each `assertSee('rel="icon"', false)`; one that Michael Scott, signed in with `actingAs()`, sees the same on `route('settings.index')` (research R4)

## Phase 4: User Story 2 - Recognize Jacques in bookmarks and history (P2)

- [X] T004 [US2] No code: browsers take the bookmark icon from the same file. Verify with step 4 of quickstart.md

## Phase 5: Polish

- [X] T005 Run `php artisan jacques:localize` and confirm it reports nothing missing (no copy changes)
- [X] T006 Run Pint, PHPStan and the full suite, and fix what they report
- [ ] T007 Run the manual scenarios of quickstart.md against `http://jacques.test` in a light and a dark browser theme

## Dependencies

- T001 → T003 (the header check reads the converted file). T002 and T003 touch different files.
- US2 depends only on T001.

## Strategy

MVP is T001 to T003: the file, the root page and the test ship together in one pull request.
