<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\SettingsControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;

it('shows the settings of the account', function () {
    $user = member();

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertStatus(200);
    $response->assertSee('Profile');
    $response->assertSee('Security and access');
    $response->assertSee('Preferences');
    $response->assertSee('Logs');
});

it('shows the name of the company and of the person', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = $this->giveDefaultRole(User::factory()->create([
        'company_id' => $company->id,
    ]), Role::STAFF);
    Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'display_name' => 'Michael Scott',
    ]);

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertSee('Dunder Mifflin');
    $response->assertSee('Michael Scott');
});

it('hides the company section from somebody who administers nothing', function () {
    $user = member();

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertDontSee('This company');
    $response->assertDontSee('Roles and permissions');
});

it('shows the locations to somebody who looks after the company', function () {
    $company = Company::factory()->create();
    $user = $this->grant(
        User::factory()->create(['company_id' => $company->id]),
        PermissionEnum::LocationManage,
    );
    Location::factory()->count(3)->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertSee('This company');
    $response->assertSee('3 locations');
});

it('shows the roles to somebody who looks after them', function () {
    $company = Company::factory()->create();
    $user = $this->grant(
        User::factory()->create(['company_id' => $company->id]),
        PermissionEnum::RoleManage,
    );

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertSee('Roles and permissions');
    $response->assertSee('1 person');
});

it('refuses somebody who is not signed in', function () {
    $this->get(route('settings.index'))->assertRedirect(route('auth.signIn.new'));
});

function member(): User
{
    $company = Company::factory()->create();

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::STAFF);
}

it('lets a keyboard skip straight to the content', function () {
    $user = member();

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertSee('href="#main"', escape: false);
    $response->assertSee('Skip to content');
    $response->assertSee('<main id="main"', escape: false);
});
