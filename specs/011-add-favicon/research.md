# Research: Add Favicon

## R1. File formats

**Decision**: three files at the root of `public/`, announced in this order by
`resources/views/partials/meta.blade.php`:

| File | Link | Role |
| --- | --- | --- |
| `favicon-96x96.png` | `rel="icon" type="image/png" sizes="96x96"` | sharp raster icon |
| `favicon.svg` | `rel="icon" type="image/svg+xml"` | sharp at any density |
| `favicon.ico` | `rel="shortcut icon"` | fallback for older browsers, and `/favicon.ico` (FR-002) |

**Rationale**: this is the markup supplied with the request. Each browser takes the format it reads
best, and the ICO stays at the conventional address browsers request on their own.

**Alternatives considered**: a single ICO (blurry on high density screens).

## R2. Conversion

**Decision**: the ICO is converted once with ImageMagick, `magick
specs/011-add-favicon/favicon-source.png public/favicon.ico`. The PNG and the SVG are committed as
supplied. No build step and no package.

**Rationale**: the icon changes almost never. A conversion in the Vite build or a PHP image package
would add a dependency for files produced once.

## R3. The root page

**Decision**: add the same three links to `resources/views/welcome.blade.php`.

**Rationale**: `/` renders that view for a guest, and it does not include the shared page head.
FR-001 asks every page to announce the icon; the fallback alone satisfies FR-002, not FR-001.

**Alternatives considered**: including `partials.meta` in the welcome view (changes its title and
adds the theme script to a page this feature does not own).

## R4. Test

**Decision**: one feature test, `tests/Feature/FaviconTest.php`, beside `SessionCookieTest.php`. It
checks the first bytes of each file (`00 00 01 00` for the ICO, `\x89PNG` for the PNG, `<svg` for
the SVG), and that the sign in screen, a signed in screen and `/` each carry the three links.

**Rationale**: the defect being fixed is an empty file that nothing noticed. A header check fails on
a zero byte file and on a file saved in the wrong format.

## R5. Local server

Herd answers files at the root of `public/` (`favicon.ico`, `robots.txt`) with status `404` and the
right body. The PNG and the SVG answer `200`. This is the local server, not the application.
