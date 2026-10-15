# Research: Add Favicon

## R1. File format

**Decision**: one ICO file holding the 32 by 32 image, at `public/favicon.ico`, the path every
layout already announces through `resources/views/partials/meta.blade.php`.

**Rationale**: browsers request `/favicon.ico` on their own when a page announces nothing, so this
one file covers FR-002 and the root page. An ICO keeps the existing `<link>` correct without
touching its `sizes="32x32"`.

**Alternatives considered**: a PNG beside the ICO (a second file for no visible gain at one size);
an SVG icon (no vector source exists).

## R2. Conversion

**Decision**: convert once with ImageMagick, `magick specs/011-add-favicon/favicon-source.png
public/favicon.ico`, and commit the result. No build step and no package.

**Rationale**: the icon changes almost never. A conversion in the Vite build or a PHP image package
would add a dependency for a file produced once.

## R3. The root page

**Decision**: add the same `<link rel="icon">` line to `resources/views/welcome.blade.php`.

**Rationale**: `/` renders that view for a guest, and it does not include the shared page head.
FR-001 asks every page to announce the icon; the fallback alone satisfies FR-002, not FR-001.

**Alternatives considered**: including `partials.meta` in the welcome view (changes its title and
adds the theme script to a page this feature does not own).

## R4. Test

**Decision**: one feature test, `tests/Feature/FaviconTest.php`, beside `SessionCookieTest.php`. It
checks that the file exists, is not empty and starts with the ICO header bytes `00 00 01 00`, and
that the sign in screen, a signed in screen and `/` each contain `rel="icon"`.

**Rationale**: the defect being fixed is an empty file that nothing noticed. The header check fails
on a zero byte file and on a PNG renamed to `.ico`.
