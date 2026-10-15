# Tasks: Add Favicon

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by FR-004 and constitution principle III. The test follows `.claude/rules/testing.md` and uses names from The Office.

## Phase 1: Setup

No setup: ImageMagick (`magick`) is already on the developer machine, and no package is added.

## Phase 2: Foundational

- [X] T001 Replace the empty public/favicon.ico with `magick specs/011-add-favicon/favicon-source.png public/favicon.ico`, and confirm `file public/favicon.ico` reports one 32 by 32 icon (research R1, R2)
- [X] T002 Commit the supplied public/favicon-96x96.png and public/favicon.svg as they are (research R1, R2)

## Phase 3: User Story 1 - Recognize Jacques among open tabs (P1) MVP

- [X] T003 [US1] Replace the single icon link in resources/views/partials/meta.blade.php with the three links of research R1, in its order
- [X] T004 [US1] Add the same three links after the `<title>` in resources/views/welcome.blade.php (research R3)
- [X] T005 [P] [US1] Create tests/Feature/FaviconTest.php: the first bytes of each of the three files, and the three links on a guest GET of `route('auth.signIn.new')` and of `/`, and on `route('settings.index')` for Michael Scott signed in with `actingAs()` (research R4)

## Phase 4: User Story 2 - Recognize Jacques in bookmarks and history (P2)

- [X] T006 [US2] No code: browsers take the bookmark icon from the same files. Verify with step 4 of quickstart.md

## Phase 5: Polish

- [X] T007 Run `php artisan jacques:localize` and confirm it reports nothing missing (no copy changes)
- [X] T008 Run Pint, PHPStan and the full suite, and fix what they report
- [ ] T009 Run the manual scenarios of quickstart.md against `http://jacques.test` in a light and a dark browser theme

## Dependencies

- T001 and T002 → T005 (the header checks read the files). T003, T004 and T005 touch different files.
- US2 depends only on T001 and T002.

## Strategy

MVP is T001 to T005: the files, the links and the test ship together in one pull request.
