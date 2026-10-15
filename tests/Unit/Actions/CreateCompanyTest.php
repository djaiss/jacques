<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateCompany;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateCompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_company_and_its_owner(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Dunder Mifflin',
            'slug' => 'dunder-mifflin',
        ]);
        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $owner = $company->users()->firstOrFail();

        $this->assertTrue(Hash::check('thatswhatshesaid', $owner->password_hash));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyCreated
                && $job->company->id === $company->id
                && $job->user->id === $owner->id,
        );
    }

    #[Test]
    public function it_creates_the_employee_the_owner_is(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertDatabaseHas('employees', [
            'company_id' => $company->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'work_email' => 'michael.scott@dundermifflin.com',
        ]);

        $owner = $company->users()->firstOrFail();

        $this->assertNotNull($owner->employee_id);
        $this->assertEquals('Michael Scott', $owner->employee->name);
    }

    #[Test]
    public function it_gives_the_company_the_roles_it_starts_with(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertEquals(
            [Role::MANAGER, Role::OWNER, Role::RECEPTIONIST, Role::STAFF],
            $company->roles()->pluck('slug')->sort()->values()->all(),
        );
    }

    #[Test]
    public function it_gives_the_first_user_the_owner_role(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $owner = $company->users()->firstOrFail();

        $this->assertEquals([Role::OWNER], $owner->roles->pluck('slug')->all());
        $this->assertTrue(
            $owner->permission(PermissionEnum::BillingManage)->forCompany($company)->allowed(),
        );
    }

    #[Test]
    public function it_starts_the_company_on_a_trial(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertTrue($company->isOnTrial());
    }

    #[Test]
    public function it_owes_the_new_company_the_welcome_screen(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertTrue($company->refresh()->is_welcome_screen_pending);
    }

    #[Test]
    public function it_makes_the_slug_unique(): void
    {
        Queue::fake();

        $first = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $second = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Jim',
            lastName: 'Halpert',
            email: 'jim.halpert@dundermifflin.com',
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertEquals('dunder-mifflin', $first->slug);
        $this->assertEquals('dunder-mifflin-2', $second->slug);
    }
}
