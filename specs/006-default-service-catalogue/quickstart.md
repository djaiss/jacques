# Quickstart: Default Service Catalogue

## Checks

```bash
php artisan test --compact --filter='CreateDefaultCatalogue|OfferServicesAtLocation|CreateDefaultServices|CreateCompany|SetUpSalon'
```

```bash
php artisan jacques:localize --check
```

## By hand

1. `php artisan migrate:fresh --seed`, then run `php artisan queue:work --queue=high,default,low --stop-when-empty`.
2. Sign up a new company, run the worker again, dismiss the welcome screen.
3. `php artisan tinker`: the company has 6 categories, 17 services, 20 steps, and `default_catalogue_created_at` set.
4. Submit step 1 with Canada (English and French): the headquarters offers 17 services, prices null.
5. Read a service name: it holds `en`, `fr`, `de` and `es`.
6. Dispatch `CreateDefaultServices` again for the company: counts are unchanged.
7. Create a second location: it offers nothing.

## Expected

| Check | Result |
| --- | --- |
| Full Color steps | 30 + 30, then 30 (90 minutes) |
| Consultation | one step of 15, price 0 |
| Job run twice | 6, 17, 20, 17 records |
| Step 1 before the worker runs | location offers 17 services once the worker runs |
