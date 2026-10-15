<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create();
        $category = ServiceCategory::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($category->company()->exists());
        $this->assertEquals($company->id, $category->company->id);
    }

    #[Test]
    public function it_has_many_services_in_the_order_of_their_position(): void
    {
        $category = ServiceCategory::factory()->create();
        $colour = Service::factory()->create(['company_id' => $category->company_id, 'service_category_id' => $category->id, 'position' => 2]);
        $haircut = Service::factory()->create(['company_id' => $category->company_id, 'service_category_id' => $category->id, 'position' => 1]);

        $this->assertTrue($category->services()->exists());
        $this->assertEquals([$haircut->id, $colour->id], $category->services->pluck('id')->all());
    }
}
