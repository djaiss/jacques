# Quickstart: Team members screen

## Prerequisites

`composer run dev`, a salon past onboarding with Michael Scott as owner, and in its team Jim
Halpert (signs in, Staff), Pam Beesly (prepared invitation) and Toby Flenderson (no account).

## Automated checks

```bash
php artisan test --compact --filter=Team
```

```bash
php artisan test --compact --filter=Role
```

```bash
php artisan jacques:localize --check
```

```bash
vendor/bin/pint --test -q && vendor/bin/phpstan analyse --no-progress -q
```

## Manual scenarios

| # | Do | Expect |
| --- | --- | --- |
| 1 | Open Settings › Team members as Michael | Four rows, "You" on Michael; counts Everybody 4, Signs in 2, Invited 1, No account 1 |
| 2 | Open the tabs Invited, then No account | Only Pam at `team/invited`, then only Toby at `team/no-account` |
| 3 | Manage Toby, pick Manager, Send an invitation | Panel stays open, reads Invitation sent; the email names Manager |
| 4 | Manage Jim, pick Manager, Save changes | Back on the list, "The access of Jim Halpert is saved.", Jim reads Manager |
| 5 | Manage Michael | Notice about the only owner; Staff and Manager disabled; no Remove the account |
| 6 | Manage Jim, Remove the account, Keep it, then Remove it | Nothing on Keep it; then Jim reads No account and is still bookable |
| 7 | Open `settings/administration/team/{id}` of another company's staff | 404 |
| 8 | Keyboard only at 375, 768 and 1280 px, then zoom 200% | Panel traps focus, Escape closes and returns focus to Manage; nothing overflows |
