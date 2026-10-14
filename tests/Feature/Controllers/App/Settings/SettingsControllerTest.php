<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_settings_of_the_account(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Profile');
        $response->assertSee('Security and access');
        $response->assertSee('Preferences');
        $response->assertSee('Logs');
    }

    #[Test]
    public function it_shows_the_name_of_the_company_and_of_the_person(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'display_name' => 'Michael Scott',
        ]);
        $user = $this->giveDefaultRole(User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]), Role::STAFF);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertSee('Dunder Mifflin');
        $response->assertSee('Michael Scott');
    }

    #[Test]
    public function it_hides_the_company_section_from_somebody_who_administers_nothing(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertDontSee('This company');
        $response->assertDontSee('Roles and permissions');
    }

    #[Test]
    public function it_shows_the_locations_to_somebody_who_looks_after_the_company(): void
    {
        $company = Company::factory()->create();
        $user = $this->grant(
            User::factory()->create(['company_id' => $company->id]),
            PermissionEnum::LocationManage,
        );
        Location::factory()->count(3)->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertSee('This company');
        $response->assertSee('3 locations');
    }

    #[Test]
    public function it_shows_the_roles_to_somebody_who_looks_after_them(): void
    {
        $company = Company::factory()->create();
        $user = $this->grant(
            User::factory()->create(['company_id' => $company->id]),
            PermissionEnum::RoleManage,
        );

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertSee('Roles and permissions');
        $response->assertSee('1 person');
    }

    #[Test]
    public function it_refuses_somebody_who_is_not_signed_in(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('auth.signIn.new'));
    }

    private function member(): User
    {
        $company = Company::factory()->create();

        return $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::STAFF);
    }

    #[Test]
    public function it_lets_a_keyboard_skip_straight_to_the_content(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertSee('href="#main"', escape: false);
        $response->assertSee('Skip to content');
        $response->assertSee('<main id="main"', escape: false);
    }
}
