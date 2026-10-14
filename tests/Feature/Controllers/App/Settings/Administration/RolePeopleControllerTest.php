<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RolePeopleControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_a_role_to_somebody(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $colleague = User::factory()->create(['company_id' => $company->id, 'email' => 'dwight@dundermifflin.com']);
        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('settings.rolePeople.create', $role->id), [
            'user' => $colleague->id,
        ]);

        $response->assertRedirect(route('settings.roles.show', [$role->id, 'people']));
        $response->assertSessionHas('status', 'The role is handed out.');
        $this->assertDatabaseHas('user_roles', ['user_id' => $colleague->id, 'role_id' => $role->id]);
    }

    #[Test]
    public function it_takes_a_role_back(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $colleague = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);
        $colleague->roles()->attach($role->id);

        $response = $this->actingAs($user)->delete(route('settings.rolePeople.destroy', [$role->id, $colleague->id]));

        $response->assertRedirect(route('settings.roles.show', [$role->id, 'people']));
        $response->assertSessionHas('status', 'The role is taken back.');
        $this->assertDatabaseMissing('user_roles', ['user_id' => $colleague->id, 'role_id' => $role->id]);
    }

    #[Test]
    public function it_shows_who_holds_the_role(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $colleague = User::factory()->create(['company_id' => $company->id, 'email' => 'pam@dundermifflin.com']);
        $role = Role::factory()->create(['company_id' => $company->id]);
        $colleague->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('settings.roles.show', [$role->id, 'people']));

        $response->assertOk();
        $response->assertSee('pam@dundermifflin.com');
        $response->assertSee('They keep whatever their other roles grant. Anything only this role gave them, they lose.');
    }

    #[Test]
    public function it_does_not_find_somebody_from_another_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $stranger = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('settings.rolePeople.create', $role->id), [
            'user' => $stranger->id,
        ]);

        $response->assertNotFound();
    }

    #[Test]
    public function it_refuses_somebody_who_may_not_administer_the_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->giveDefaultRole($user, Role::STAFF);

        $colleague = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('settings.rolePeople.create', $role->id), [
            'user' => $colleague->id,
        ]);

        $response->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_request_that_names_nobody(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('settings.rolePeople.create', $role->id), []);

        $response->assertSessionHasErrors('user');
    }
}
