<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('shows the profile screen', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Pam',
        'last_name' => 'Beesly',
        'user_id' => $user->id,
    ]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->get(route('settings.profile.index'));

    $response->assertStatus(200);
    $response->assertSee('Pam', escape: false);
    $response->assertSee('Beesly', escape: false);
    $response->assertSee('Dunder Mifflin', escape: false);
});

it('shows the toast of the previous save', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)
        ->withSession([
            'status' => 'Your details are saved.',
            'status_description' => 'Your colleagues see them right away.',
        ])
        ->get(route('settings.profile.index'));

    $response->assertStatus(200);
    $response->assertSee('id="notifications"', escape: false);
    $response->assertSee('Your details are saved.', escape: false);
    $response->assertSee('Your colleagues see them right away.', escape: false);
});

it('carries the top bar and the way back to the settings hub', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->get(route('settings.profile.index'));

    $response->assertStatus(200);
    $response->assertSee('id="top-bar-menu"', escape: false);
    $response->assertSee('Dunder Mifflin', escape: false);
    $response->assertSee(route('settings.index'), escape: false);
});

it('points to the logs screen', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->get(route('settings.profile.index'));

    $response->assertStatus(200);
    $response->assertSee(route('settings.logs.index'), escape: false);
});

it('redirects a visitor who is not signed in', function () {
    $response = $this->get(route('settings.profile.index'));

    $response->assertRedirect(route('auth.signIn.new'));
});

it('updates the details', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->put(route('settings.profile.update'), [
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'display_name' => 'The Boss',
        'work_email' => 'michael.scott@dundermifflin.com',
    ]);

    $response->assertRedirect(route('settings.profile.index'));
    $response->assertSessionHas('status', 'Your details are saved.');
    $response->assertSessionHas('status_description', 'Your colleagues see them right away.');

    $staff->refresh();

    expect($staff->first_name)->toEqual('Michael');
    expect($staff->last_name)->toEqual('Scott');
    expect($staff->display_name)->toEqual('The Boss');
    expect($staff->email)->toEqual('michael.scott@dundermifflin.com');
});

it('keeps the details the form does not show', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->unbookable()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'phone' => '570-555-0142',
        'job_title' => 'Regional Manager',
        'bio' => 'World\'s best boss.',
    ]);
    $this->giveDefaultRole($user, Role::STAFF);

    $this->actingAs($user)->put(route('settings.profile.update'), [
        'first_name' => 'Michael',
        'last_name' => 'Scott',
    ]);

    $staff->refresh();

    expect($staff->first_name)->toEqual('Michael');
    expect($staff->phone)->toEqual('570-555-0142');
    expect($staff->job_title)->toEqual('Regional Manager');
    expect($staff->bio)->toEqual('World\'s best boss.');
    expect($staff->is_bookable)->toBeFalse();
});

it('refuses details without a name', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->put(route('settings.profile.update'), [
        'first_name' => '',
        'last_name' => '',
    ]);

    $response->assertSessionHasErrors(['first_name', 'last_name']);
});

it('refuses a work email that is not an email', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->put(route('settings.profile.update'), [
        'first_name' => 'Angela',
        'last_name' => 'Martin',
        'work_email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('work_email');
});
