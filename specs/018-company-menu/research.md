# Research: Company menu

## R1. Where the tiles are described

- **Decision**: keep the `@php` arrays at the top of `top-bar.blade.php`, one entry per tile with
  label, shortcut, hue, SVG path markup and a nullable URL. The hue drives the three `oklch` values
  of the design (background, inset ring, icon colour) through an inline `style`.
- **Rationale**: the menu already works this way; four static entries do not earn a view model.
- **Alternatives considered**: a `MenuViewModel` (more files for static data); one Blade component
  per tile (no reuse elsewhere).

## R2. A tile that leads nowhere

- **Decision**: drawn with the same classes as a linked tile, as a `<button type="button"
  aria-disabled="true">` with no hover change, so a screen reader announces it as unavailable.
- **Rationale**: the user asked for the buttons to be present; the spec asks for them undimmed. An
  `<a href="#">` would scroll to the top and add `#` to the address.
- **Alternatives considered**: `<span>` faded at 45% as today (rejected by the spec); `href="#"`
  (changes the address).

## R3. Team for a person without `RoleManage`

- **Decision**: `TopBarLayout` computes `teamUrl` with
  `permission(PermissionEnum::RoleManage)->forCompany($company)->allowed()`, the check
  `TeamController@index` authorizes with, and passes `null` otherwise.
- **Rationale**: one rule in one place; the Blade component only reads a URL.
- **Alternatives considered**: hiding the tile (breaks the four column shape of the design).

## R4. Differences with the design kept on purpose

| Design | App | Why |
| --- | --- | --- |
| Panel `top:70px`, width 560 | Same, `max-w-[92vw]` | Fits a phone |
| Four columns at every width | Two columns under `sm` | Labels stay readable at 375 pixels |
| `a:hover{color:#3f5fd0}` | Not applied to the panel | White text on dark panel stays legible |
| Logo and identity absent | Kept | Spec assumption: only the menu changes |
