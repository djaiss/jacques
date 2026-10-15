<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateServicePrices;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateServicePricesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_and_removes_prices_and_keeps_those_of_a_currency_no_longer_used(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['currencies' => ['CAD', 'USD']]);
        $service = Service::factory()->create(['company_id' => $company->id, 'prices' => ['CAD' => 3500, 'USD' => 2600, 'EUR' => 2400]]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new UpdateServicePrices(author: $michael, service: $service, prices: ['CAD' => 4000, 'USD' => null])->execute();

        $this->assertEquals(['CAD' => 4000, 'EUR' => 2400], $service->refresh()->prices);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServicePricesUpdated,
        );
    }

    #[Test]
    public function it_throws_for_a_negative_price(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = Service::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateServicePrices(author: $michael, service: $service, prices: ['CAD' => -1])->execute();
    }

    #[Test]
    public function it_throws_for_a_currency_the_salon_does_not_use(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = Service::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateServicePrices(author: $michael, service: $service, prices: ['GBP' => 1000])->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = Service::factory()->create();
        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new UpdateServicePrices(author: $michael, service: $service, prices: ['CAD' => 1000])->execute();
    }
}
