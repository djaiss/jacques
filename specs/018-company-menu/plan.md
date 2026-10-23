# Implementation Plan: Company menu

**Branch**: `2026-10-23-company-menu` | **Date**: 2026-10-23 | **Spec**: [spec.md](spec.md)

## Summary

The panel of `x-top-bar` is redrawn from `Company Menu.dc.html`: four tiles with SVG icons in a
four column grid, YOURS on two columns, the button tinted at rest. Only Dashboard and Team link;
Team only for a person holding `RoleManage`, decided once in `TopBarLayout` and passed down as a
nullable URL. Decisions: [research.md](research.md). No `data-model.md` nor `contracts/`: no data,
no route and no request change.

## Technical Context

- **Language/Version**: PHP 8.4, current Laravel
- **Primary Dependencies**: none new; Blade, Tailwind, Alpine.js (`x-trap` already used by the menu)
- **Storage**: none
- **Testing**: Pest feature tests on the dashboard response, browser test for opening the menu
- **Target Platform**: web back office, desktop to phone
- **Project Type**: Laravel web application
- **Performance Goals**: one permission check per screen, already cached by the permission layer
- **Constraints**: light and dark mode, keyboard and screen reader, 375 pixel wide screen, no dashes in prose
- **Scale/Scope**: 1 component class, 1 Blade component, translations, 2 test files

## Constitution Check

| Gate | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Nothing is written; no action involved |
| II. Simplicity | Pass. Tiles stay an array in the component, as today (R1); no new class |
| III. Tested | Pass. Feature tests for the tiles and the Team link per permission; browser test for the panel |
| IV. Laravel conventions | Pass. Anonymous Blade component fed by the existing class component |
| V. Localized and safe | Pass. Every label through `__()`, then `jacques:localize`; nothing destructive |
| VI. Accessible | Pass. Inert tiles are buttons with `aria-disabled` (R2); focus trap and `aria-expanded` kept |
| VII. One core | Not applicable: navigation only |
| VIII. Short record | Pass. Every artifact under its cap |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation

```text
specs/018-company-menu/
├── spec.md  plan.md  research.md  quickstart.md
└── tasks.md            (/speckit-tasks)
```

### Source Code

```text
app/View/Components/TopBarLayout.php            passes teamUrl (null without RoleManage)
resources/views/layouts/top-bar.blade.php       forwards teamUrl to x-top-bar
resources/views/components/top-bar.blade.php    new button, panel, tiles and YOURS grid (R1 to R4)
lang/*.json                                     through jacques:localize
tests/Feature/Controllers/App/DashboardControllerTest.php   tiles, links, Team per permission
tests/Browser/MenuTest.php                      open, close, G key, follow Dashboard
```

**Structure Decision**: the existing layout. The three settings tests asserting `id="top-bar-menu"`
keep passing since the id stays.

## Order of Work

1. `TopBarLayout` and the layout pass `teamUrl` (US1, US2).
2. Redraw `top-bar.blade.php`: button, panel, tiles, YOURS (US1, US2).
3. Feature tests, then the browser test.
4. `jacques:localize`, Pint, PHPStan, quickstart at three widths in both themes.

## Complexity Tracking

No violation to justify.
