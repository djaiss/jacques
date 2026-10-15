# Quickstart: Salon Onboarding

## Automated

```bash
php artisan test --compact --filter='Onboarding|SetUpSalon|CompleteOnboardingStep|CreateLocation|CreateCompany|WelcomeController|CompanyTest'
```

```bash
php artisan jacques:localize --check
```

## By hand

1. Sign up, confirm the email address, press the welcome screen button: `/onboarding/location`
   shows, step 1 active in the tracker, salon name prefilled with the company name.
2. Submit with an empty name: "The salon needs a name." shows and nothing is saved.
3. Pick Canada: the time zone list holds only Canadian zones, the first one selected. Submit: the
   confirmation shows, step 1 is ticked, Settings lists one headquarters location with the values.
4. Press "Edit the salon", change the city, submit: still one location, city changed.
5. Press "Continue to services", then "Finish later": home shows. Open `/onboarding`: step 2.
6. Open `/onboarding/appointments`: sent to step 2. Open `/onboarding/location`: confirmation.
7. Complete steps 2, 3 and 4: home shows. Open `/onboarding/location`: home shows.
8. Repeat step 1 on a phone width, with the keyboard only, and in dark mode.
