# Implementation Plan: Add Favicon

**Branch**: `2026-10-15-favicon` | **Date**: 2026-10-15 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/011-add-favicon/spec.md`

## Summary

`public/favicon.ico` exists but is empty, so the icon every layout announces is blank. Convert the
attached portrait to a 32 by 32 ICO in its place, add the supplied 96 by 96 PNG and SVG beside it,
announce the three formats on every page including the root page, and add a test that fails if a
file is ever empty or in the wrong format. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel

**Primary Dependencies**: none added. ImageMagick on the developer machine for the one conversion (R2).

**Storage**: none (no table, no migration)

**Testing**: PHPUnit, `tests/Feature/FaviconTest.php`

**Target Platform**: every browser the back office supports

**Project Type**: web application (Laravel monolith)

**Performance Goals**: none; the files are static and served by the web server

**Constraints**: every format shows the supplied portrait, unchanged (FR-003)

**Scale/Scope**: 3 image files, 3 links in 2 views, 1 test

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Not applicable, no operation. |
| II. Simplicity | Pass. Committed files, no build step, no package (R2). |
| III. Tested before merged | Pass. `FaviconTest` covers FR-001, FR-002 and FR-004 (R4). |
| IV. Laravel conventions | Pass. Served from `public/`, announced with `asset()`. |
| V. Localized and safe UX | Not applicable, no text and no destructive action. |
| VI. Accessible | Pass. Decorative browser chrome; nothing on the screen changes. |
| VII. One core for every surface | Not applicable, not a capability. |
| VIII. Short record | Pass. `data-model.md` and `contracts/` omitted: nothing is stored or exposed. |
| Product invariants | Not affected. |
| Folders | Pass. `public/`, `resources/views/`, `tests/Feature/`. |

Post-design re-check: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/011-add-favicon/
├── plan.md
├── research.md
├── quickstart.md
├── favicon-source.png
└── checklists/requirements.md
```

### Source Code (repository root)

```text
public/favicon.ico                         # replaced: the portrait as a 32 by 32 ICO (R1, R2)
public/favicon-96x96.png                   # supplied, committed as is (R1)
public/favicon.svg                         # supplied, committed as is (R1)
resources/views/partials/meta.blade.php    # the three links, for the three layouts (R1)
resources/views/welcome.blade.php          # the same three links (R3)
tests/Feature/FaviconTest.php              # file header and announcement checks (R4)
```

**Structure Decision**: existing Laravel folders only; no new top level folder.
