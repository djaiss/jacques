<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CopyDefaultCatalogue;
use App\Models\Company;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CopyDefaultCatalogueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_example_after_the_categories_already_there(): void
    {
        $company = Company::factory()->create();
        ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);

        new CopyDefaultCatalogue(company: $company)->execute();

        $this->assertEquals(range(1, 7), ServiceCategory::query()->where('company_id', $company->id)->orderBy('position')->pluck('position')->all());
    }
}
