# Research: Team members screen

## R1. The panel is a URL, as in onboarding step 3

- **Decision**: `GET settings/administration/team/{staff}` renders the team screen with the panel of that person open, like `onboarding.staff.members.edit`. Every form in the panel redirects back to it, so errors and refusals show inside the panel (spec edge cases).
- **Rationale**: reuses `x-side-modal`, focus trapping and the Escape handling already shipped; the panel survives a reload and a failed validation. It opens over the tab it came from; after a change, the page reloads on the Everybody tab.
- **Alternatives**: an Alpine only panel fed by JSON, rejected (the back office stays in Blade, constitution VII).

## R2. One address per tab

- **Decision**: each tab is a route with its own controller and view model; `TeamSignsInViewModel`, `TeamInvitedViewModel` and `TeamNoAccountViewModel` extend `TeamViewModel` and name the account state they list. All four render the same view.
- **Rationale**: asked during implementation; a tab can be bookmarked and shared. No query string (code style).
- **Alternatives**: filtering in Alpine, the first version, replaced.

## R3. One role per person through `AssignRole`

- **Decision**: `AssignRole` replaces the role a person holds (`sync`) instead of adding one, and refuses to move the last active owner away from the Owner role, the guard `RemoveRole` already has. `AcceptStaffInvitation` and `CreateCompany` also `sync`. `user_roles.user_id` becomes unique, by editing its migration (no production data, specs/009 R1).
- **Rationale**: one action for the role editor's people tab and the panel, so both obey the same rule (constitution I).
- **Alternatives**: a new `ChangeUserRole` action beside `AssignRole`, rejected: two actions doing one operation.
- `RemoveRole` stays for the role editor; a person can then hold no role and has no permission.

## R4. The role of somebody without an account lives on the invitation

- **Decision**: the Role section stores the user's role when an account exists, the invitation's role when one exists, and otherwise travels with Send an invitation or Prepare it for later as `role_id`. `SendStaffInvitation` gains an optional role, prepared first when no invitation exists.
- **Rationale**: the design shows one role picker whatever the account state.

## R5. Removing an account is `DestroyUser`

- **Decision**: the panel calls `DestroyUser`, which also deletes the staff invitation in the same transaction, so the person reads No account and can be invited again. `staff.user_id` is already `nullOnDelete`; logs keep their row with a null author.
- **Rationale**: the existing action already forbids deleting oneself and checks ownership; the spec's "not the viewer" rule is that check.
- `DestroyUser` checks `isOwner()`, stricter than FR-011's role manage permission; kept, since deleting an account is an owner decision in D4.

## R6. Permissions shown read only

- **Decision**: the panel lists the permissions of the selected role grouped by `PermissionGroupEnum`, with the scope label the role editor shows, and a link to `settings.roles.show`. Switching cards swaps the list in Alpine from data embedded per role.
- **Alternatives**: reloading the panel per card, rejected (a request per click for static data).

## R7. Gaps

- No public API nor webhook exists yet; the screen adds none (constitution VII, same gap as specs/016 R12).
- D4 in `docs/specs/decisions.md` is amended first: "Une personne porte un seul rôle."
