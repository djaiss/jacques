<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;

it('shows the settings to a signed in person', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'display_name' => 'Michael Scott',
    ]);

    $this->actingAs($user);

    $page = visit(route('settings.index'));

    $page->assertSee('Settings')
        ->assertSee('Michael Scott')
        ->assertNoJavaScriptErrors();
});

it('shows the settings on a phone', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('settings.index'))->on()->mobile();

    $page->assertSee('Profile')
        ->assertSee('Security and access')
        ->assertNoJavaScriptErrors();
});

it('leads to every screen of the account and back', function (string $row, string $path) {
    $this->actingAs(User::factory()->create());

    $page = visit(route('settings.index'));

    $page->assertSee($row)
        ->click("section a[href$=\"{$path}\"]")
        ->assertPathIs($path)
        ->assertNoJavaScriptErrors()
        ->click('nav[aria-label="Breadcrumb"] a[href$="/settings"]')
        ->assertPathIs('/settings');
})->with('account rows');

it('leads to the screens of the company for an administrator', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::LocationManage);
    $this->grant($user, PermissionEnum::RoleManage);
    $this->actingAs($user);

    $page = visit(route('settings.index'));

    $page->assertSee('This company')
        ->click('section a[href$="/settings/administration/locations"]')
        ->assertPathIs('/settings/administration/locations')
        ->assertNoJavaScriptErrors();

    $page = visit(route('settings.index'));

    $page->click('section a[href$="/settings/administration/roles"]')
        ->assertPathIs('/settings/administration/roles')
        ->assertNoJavaScriptErrors();
});

it('keeps the company out of reach of somebody who administers nothing', function () {
    $this->actingAs(User::factory()->create());

    visit(route('settings.index'))
        ->assertSee('You')
        ->assertDontSee('This company')
        ->assertDontSee('Roles and permissions');

    visit(route('settings.roles.index'))->assertDontSee('New role');
    visit(route('settings.locations.index'))->assertDontSee('Add a location');
});

dataset('account rows', [
    'profile' => ['Profile', '/settings/account/profile'],
    'security' => ['Security and access', '/settings/account/security'],
    'preferences' => ['Preferences', '/settings/account/preferences'],
    'logs' => ['Logs', '/settings/account/logs'],
]);
