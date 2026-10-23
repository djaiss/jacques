# Quickstart: Client records

## Setup

```bash
composer install -q
php artisan migrate:fresh -q
```

## Validation

| Scenario | Command | Expected |
| --- | --- | --- |
| Models, relations and casts | `php artisan test --compact tests/Unit/Models` | green |
| Phone normalization (US 1, R1) | `php artisan test --compact --filter=PhoneNumberTest` | "(570) 555-0100" in US and "+1 570 555 0100" both give `+15705550100` |
| Phone unique per company (SC-001) | `php artisan test --compact --filter=ClientTest` | a duplicate in one company throws, the same phone in two companies passes |
| Snapshot survives catalogue edits (SC-002) | `php artisan test --compact --filter=AppointmentServiceTest` | price unchanged after the service price changes |
| Indicators (US 4, SC-003) | `php artisan test --compact --filter=ClientIndicatorsTest` | Jan 1, Feb 5, Mar 12 give an interval of 35 days; six months idle gives Inactive |
| Staff with appointments (D3) | `php artisan test --compact --filter=DestroyStaffTest` | deletion refused |
| Erasure readiness (SC-004) | `php artisan test --compact --filter=ClientTest` | personal fields set to null, appointments still counted |
| Three databases | CI `tests.yml` | green on SQLite, MySQL, PostgreSQL |
| Static checks | `vendor/bin/pint --test -q && vendor/bin/phpstan analyse --no-progress -q` | no finding |
