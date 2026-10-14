<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($employee->company()->exists());
        $this->assertEquals('Dunder Mifflin', $employee->company->name);
    }

    #[Test]
    public function it_has_one_user(): void
    {
        $employee = Employee::factory()->create();
        User::factory()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertTrue($employee->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $employee->user->email);
    }

    #[Test]
    public function it_returns_the_legal_name_as_the_name_when_the_employee_goes_by_no_other_name(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => null,
        ]);

        $this->assertEquals('Michael Scott', $employee->name);
    }

    #[Test]
    public function it_returns_the_display_name_as_the_name_when_the_employee_goes_by_another_name(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'display_name' => 'Assistant to the Regional Manager',
        ]);

        $this->assertEquals('Assistant to the Regional Manager', $employee->name);
    }

    #[Test]
    public function it_returns_the_full_name(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'display_name' => 'Pamela',
        ]);

        $this->assertEquals('Pam Beesly', $employee->full_name);
    }

    #[Test]
    public function it_has_an_avatar_when_a_path_was_stored(): void
    {
        $employee = Employee::factory()->create(['avatar_path' => 'avatars/1/stanley']);

        $this->assertTrue($employee->hasAvatar());
    }

    #[Test]
    public function it_has_no_avatar_by_default(): void
    {
        $employee = Employee::factory()->create();

        $this->assertFalse($employee->hasAvatar());
    }

    #[Test]
    public function it_names_each_version_of_the_avatar_after_its_size(): void
    {
        $employee = Employee::factory()->create(['avatar_path' => 'avatars/1/stanley']);

        $this->assertEquals('avatars/1/stanley_96.webp', $employee->avatarVariantPath(96));
        $this->assertEquals('avatars/1/stanley_192.webp', $employee->avatarVariantPath(192));
    }

    #[Test]
    public function it_writes_the_displayed_size_and_twice_it(): void
    {
        $this->assertEquals([96, 192], Employee::avatarPixelSizes());
    }
}
