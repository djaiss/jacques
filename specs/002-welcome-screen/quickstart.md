# Quickstart: Welcome Screen

## Automated

```bash
php artisan test --compact --filter='Welcome|CreateCompany|CompanyTest'
```

```bash
php artisan jacques:localize --check
```

Both pass.

## By hand

1. `php artisan migrate:fresh --seed`, then sign up with a new address.
2. The email confirmation notice shows, as before.
3. Open `/settings` directly: the welcome screen shows, greeting the first name typed at sign up.
4. Click the confirmation link in the email: the welcome screen shows again.
5. Press "Let's get started": home shows.
6. Open `/welcome` and `/settings`: neither shows the welcome screen again.
7. Sign in with a seeded user: no welcome screen.
8. Repeat step 3 on a phone width, in dark mode, with the keyboard only and with VoiceOver.
