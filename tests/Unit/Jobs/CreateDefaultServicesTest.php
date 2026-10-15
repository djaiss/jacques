<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\CreateDefaultServices;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultServicesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_copies_the_default_catalogue_into_the_company(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultServices(companyId: $company->id)->handle();

        $this->assertEquals(6, ServiceCategory::query()->where('company_id', $company->id)->count());
        $this->assertEquals(17, Service::query()->where('company_id', $company->id)->count());
        $this->assertNotNull($company->refresh()->default_catalogue_created_at);
    }

    #[Test]
    public function it_fails_for_a_company_that_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateDefaultServices(companyId: 404)->handle();
    }
}
