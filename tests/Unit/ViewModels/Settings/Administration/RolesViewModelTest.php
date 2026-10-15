<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Settings\Administration\RolesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RolesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_roles_of_the_company_with_what_each_one_covers(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Regional manager']);
        RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::StaffView]);
        RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::CompanyManage]);
        $user->roles()->attach($role->id);

        Role::factory()->create(['company_id' => $company->id, 'name' => 'Temp']);
        Role::factory()->create(['company_id' => Company::factory()->create()->id, 'name' => 'Elsewhere']);

        $rows = new RolesViewModel(user: $user, role: $role)->rows();

        $this->assertCount(2, $rows);
        $this->assertEquals('Regional manager', $rows[0]['name']);
        $this->assertEquals('Administration and Team', $rows[0]['summary']);
        $this->assertEquals('2 of 17 permissions', $rows[0]['permissions']);
        $this->assertEquals('1 person', $rows[0]['holders']);
        $this->assertEquals('Temp', $rows[1]['name']);
        $this->assertEquals('Nothing yet. Tick what it is allowed to do.', $rows[1]['summary']);
        $this->assertEquals('nobody', $rows[1]['holders']);
    }

    #[Test]
    public function it_says_a_role_granting_everything_grants_everything(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Administrator']);

        foreach (PermissionEnum::cases() as $permission) {
            RolePermission::factory()->create(['role_id' => $role->id, 'permission' => $permission]);
        }

        $rows = new RolesViewModel(user: $user, role: null)->rows();

        $this->assertEquals('Everything, including handing out roles.', $rows[0]['summary']);
        $this->assertEquals([['label' => 'Full access', 'tone' => 'accent']], $rows[0]['badges']);
    }

    #[Test]
    public function it_marks_a_role_the_application_looks_after_itself(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->locked()->create(['company_id' => $company->id]);

        $rows = new RolesViewModel(user: $user, role: null)->rows();

        $this->assertEquals([['label' => 'Not editable', 'tone' => 'neutral']], $rows[0]['badges']);
        $this->assertFalse(new RolesViewModel(user: $user, role: $role)->role()['isEditable']);
    }

    #[Test]
    public function it_names_the_two_halves_of_a_role_and_says_which_one_is_being_read(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::StaffView]);
        $user->roles()->attach($role->id);

        $tabs = new RolesViewModel(user: $user, role: $role, onPeopleTab: true)->tabs();

        $this->assertEquals('permissions', $tabs[0]['key']);
        $this->assertEquals('Permissions · 1', $tabs[0]['label']);
        $this->assertFalse($tabs[0]['current']);
        $this->assertEquals('people', $tabs[1]['key']);
        $this->assertEquals('People · 1', $tabs[1]['label']);
        $this->assertTrue($tabs[1]['current']);
        $this->assertEquals(route('settings.roles.show', [$role->id, 'people']), $tabs[1]['url']);
    }

    #[Test]
    public function it_marks_the_permissions_the_role_grants_and_the_scope_of_each(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => PermissionEnum::StaffUpdate,
            'scope' => ScopeEnum::Self,
        ]);

        $groups = new RolesViewModel(user: $user, role: $role)->groups();

        $permissions = collect($groups)->pluck('permissions')->flatten(1)->keyBy('value');

        $this->assertTrue($permissions[PermissionEnum::StaffUpdate->value]['granted']);
        $this->assertEquals(ScopeEnum::Self->value, $permissions[PermissionEnum::StaffUpdate->value]['scope']);

        $this->assertFalse($permissions[PermissionEnum::StaffView->value]['granted']);
        $this->assertEquals(ScopeEnum::Company->value, $permissions[PermissionEnum::StaffView->value]['scope']);
    }

    #[Test]
    public function it_offers_no_scope_for_a_permission_that_covers_the_whole_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        $groups = new RolesViewModel(user: $user, role: $role)->groups();

        $permissions = collect($groups)->pluck('permissions')->flatten(1)->keyBy('value');

        $this->assertEquals([], $permissions[PermissionEnum::CompanyManage->value]['scopes']);
        $this->assertCount(3, $permissions[PermissionEnum::StaffView->value]['scopes']);
        $this->assertCount(2, $permissions[PermissionEnum::ClientView->value]['scopes']);
    }

    #[Test]
    public function it_shows_the_description_of_a_role_in_place_of_its_summary(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Role::factory()->create(['company_id' => $company->id, 'description' => 'Answers the phone at Dunder Mifflin.']);

        $rows = new RolesViewModel(user: $user)->rows();

        $this->assertEquals('Answers the phone at Dunder Mifflin.', $rows[0]['summary']);
    }

    #[Test]
    public function it_counts_what_the_role_grants_out_of_everything_on_offer(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::StaffView]);

        $viewModel = new RolesViewModel(user: $user, role: $role);

        $this->assertEquals('1 of '.count(PermissionEnum::cases()).' granted', $viewModel->grantCountLabel());
    }

    #[Test]
    public function it_maps_every_permission_to_whether_the_role_grants_it(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::StaffView]);

        $granted = new RolesViewModel(user: $user, role: $role)->grantedPermissions();

        $this->assertCount(count(PermissionEnum::cases()), $granted);
        $this->assertTrue($granted[PermissionEnum::StaffView->value]);
        $this->assertFalse($granted[PermissionEnum::StaffUpdate->value]);
    }

    #[Test]
    public function it_words_the_count_of_permissions_granted_for_every_number_it_can_reach(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        $viewModel = new RolesViewModel(user: $user, role: $role);
        $total = count(PermissionEnum::cases());

        $this->assertCount($total + 1, $viewModel->grantCountLabels());
        $this->assertEquals('0 of '.$total.' granted', $viewModel->grantCountLabels()[0]);
        $this->assertEquals($total.' of '.$total.' granted', $viewModel->grantCountLabels()[$total]);

        $this->assertCount($total + 1, $viewModel->permissionTabLabels());
        $this->assertEquals('Permissions · 0', $viewModel->permissionTabLabels()[0]);
        $this->assertEquals('Permissions · '.$total, $viewModel->permissionTabLabels()[$total]);
    }

    #[Test]
    public function it_words_the_count_of_permissions_granted_in_a_group_for_every_number_it_can_reach(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        $groups = new RolesViewModel(user: $user, role: $role)->groups();

        foreach ($groups as $group) {
            $size = count($group['permissions']);

            $this->assertCount($size + 1, $group['counts']);
            $this->assertEquals('0 of '.$size.' granted', $group['counts'][0]);
            $this->assertEquals($size.' of '.$size.' granted', $group['counts'][$size]);
        }
    }

    #[Test]
    public function it_warns_about_a_role_that_administers_the_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $quiet = Role::factory()->create(['company_id' => $company->id]);
        $this->assertFalse(new RolesViewModel(user: $user, role: $quiet)->warnsAboutAdministration());

        $loud = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create(['role_id' => $loud->id, 'permission' => PermissionEnum::RoleManage]);

        $this->assertTrue(new RolesViewModel(user: $user, role: $loud)->warnsAboutAdministration());
    }

    #[Test]
    public function it_splits_the_company_into_who_holds_the_role_and_who_does_not(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'email' => 'michael@dundermifflin.com']);
        $colleague = User::factory()->create(['company_id' => $company->id, 'email' => 'jim@dundermifflin.com']);
        User::factory()->create(['company_id' => Company::factory()->create()->id, 'email' => 'stranger@example.com']);

        $role = Role::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($role->id);

        $viewModel = new RolesViewModel(user: $user, role: $role);

        $this->assertCount(1, $viewModel->people());
        $this->assertEquals('michael@dundermifflin.com', $viewModel->people()[0]['email']);

        $this->assertCount(1, $viewModel->assignable());
        $this->assertEquals('jim@dundermifflin.com', $viewModel->assignable()[0]['email']);
    }

    #[Test]
    public function it_names_the_people_of_a_role_after_their_staff_record(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'email' => 'michael@dundermifflin.com']);
        $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        User::factory()->create(['company_id' => $company->id, 'email' => 'jim@dundermifflin.com']);

        $role = Role::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($role->id);

        $viewModel = new RolesViewModel(user: $user, role: $role);

        $this->assertEquals('Michael Scott', $viewModel->people()[0]['name']);
        $this->assertTrue($staff->is($viewModel->people()[0]['staff']));
        $this->assertEquals('jim@dundermifflin.com', $viewModel->assignable()[0]['name']);
        $this->assertNull($viewModel->assignable()[0]['staff']);
    }

    #[Test]
    public function it_says_why_a_role_cannot_be_deleted(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $free = Role::factory()->create(['company_id' => $company->id]);
        $viewModel = new RolesViewModel(user: $user, role: $free);
        $this->assertTrue($viewModel->canBeDeleted());
        $this->assertNull($viewModel->deleteHint());

        $held = Role::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($held->id);
        $viewModel = new RolesViewModel(user: $user, role: $held);
        $this->assertFalse($viewModel->canBeDeleted());
        $this->assertEquals('held by 1', $viewModel->deleteHint());

        $locked = Role::factory()->locked()->create(['company_id' => $company->id]);
        $viewModel = new RolesViewModel(user: $user, role: $locked);
        $this->assertFalse($viewModel->canBeDeleted());
        $this->assertEquals('not editable', $viewModel->deleteHint());
    }

    #[Test]
    public function it_holds_nothing_when_the_company_has_no_roles_left(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $viewModel = new RolesViewModel(user: $user, role: null);

        $this->assertNull($viewModel->role());
        $this->assertEquals([], $viewModel->rows());
        $this->assertEquals([], $viewModel->tabs());
        $this->assertEquals([], $viewModel->people());
        $this->assertEquals([], $viewModel->assignable());
        $this->assertFalse($viewModel->canBeDeleted());
    }

    #[Test]
    public function it_writes_out_what_each_scope_of_a_permission_means(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        $groups = new RolesViewModel(user: $user, role: $role)->groups();

        $permissions = collect($groups)->pluck('permissions')->flatten(1)->keyBy('value');

        $this->assertEquals([
            ScopeEnum::Self->value => 'Themselves only',
            ScopeEnum::Location->value => 'Their locations',
            ScopeEnum::Company->value => 'Everybody in the company',
        ], $permissions[PermissionEnum::StaffView->value]['scopes']);
    }

    #[Test]
    public function it_keeps_the_last_owner_from_being_taken_off_the_owner_role(): void
    {
        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
        $owner = $company->roles()->where('slug', Role::OWNER)->firstOrFail();

        $people = new RolesViewModel(user: $michael, role: $owner)->people();

        $this->assertFalse($people[0]['isRemovable']);

        $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

        $people = new RolesViewModel(user: $michael, role: $owner)->people();

        $this->assertTrue($people[0]['isRemovable']);
        $this->assertTrue($people[1]['isRemovable']);
    }
}
