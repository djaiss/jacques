# Contracts: Default Service Catalogue

## Job `App\Jobs\CreateDefaultServices`

```php
public function __construct(public int $companyId) {}
public int $tries = 3;
public int $backoff = 10;
public function handle(): void  // Company::query()->findOrFail($companyId), then CreateDefaultCatalogue
```

Dispatched by `CreateCompany::execute()` after its transaction:
`CreateDefaultServices::dispatch(companyId: $this->company->id)->onQueue('high')->afterCommit()`.

## Action `App\Actions\CreateDefaultCatalogue`

```php
public function __construct(private readonly Company $company) {}
public function execute(): void
```

In one transaction: lock the company; return when `default_catalogue_created_at` is set; write the
categories, services and steps; set the date; run `OfferServicesAtLocation` for the headquarters
when it exists. No author, no permission check, no user action logged: nobody did it.

## Action `App\Actions\OfferServicesAtLocation`

```php
public function __construct(private readonly Location $location) {}
public function execute(): void
```

Inserts a `LocationService` (price null, active) for every active service of the location's
company not offered there yet. Called by `CreateDefaultCatalogue` and by `SetUpSalon` right after
it creates the first location.

## Changed action `App\Actions\SetUpSalon`

`saveFirstLocation()` runs `OfferServicesAtLocation` on the location it just created; the update
branch does not.
