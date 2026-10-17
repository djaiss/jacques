<?php

declare(strict_types=1);

use App\Actions\UpdateStaffAvatar;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('saves a new name and shows it', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'first_name' => 'Michael',
        'last_name' => 'Scott',
    ]);
    $this->giveDefaultRole($user, Role::STAFF);
    $this->actingAs($user);

    $page = visit(route('settings.profile.index'));

    $page->assertValue('first_name', 'Michael')
        ->fill('first_name', 'Dwight')
        ->fill('last_name', 'Schrute')
        ->press('Save')
        ->assertValue('first_name', 'Dwight')
        ->assertValue('last_name', 'Schrute')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'first_name' => 'Dwight',
        'last_name' => 'Schrute',
    ]);
});

it('shows an error for an invalid email and keeps what was typed', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'first_name' => 'Pam',
        'last_name' => 'Beesly',
        'email' => null,
    ]);
    $this->giveDefaultRole($user, Role::STAFF);
    $this->actingAs($user);

    $page = visit(route('settings.profile.index'));

    $page->fill('first_name', 'Pamela')
        ->fill('work_email', 'pam..beesly@dundermifflin.com')
        ->press('Save')
        ->assertSee('email')
        ->assertValue('first_name', 'Pamela')
        ->assertValue('work_email', 'pam..beesly@dundermifflin.com');

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'first_name' => 'Pam',
    ]);
});

it('saves the profile on a phone', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'first_name' => 'Jim',
        'last_name' => 'Halpert',
    ]);
    $this->giveDefaultRole($user, Role::STAFF);
    $this->actingAs($user);

    $page = visit(route('settings.profile.index'))->on()->mobile();

    $page->fill('display_name', 'Big Tuna')
        ->press('Save')
        ->assertValue('display_name', 'Big Tuna')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'display_name' => 'Big Tuna',
    ]);
});

it('refuses an avatar that is not an image', function () {
    Storage::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'avatar_path' => null,
    ]);
    $this->giveDefaultRole($user, Role::STAFF);
    $this->actingAs($user);

    $document = UploadedFile::fake()->create('beets.pdf', 10, 'application/pdf');

    $page = visit(route('settings.profile.index'));

    $page->attach('avatar', $document->getPathname())
        ->press('Upload')
        ->assertSee('The avatar field must be an image.')
        ->assertDontSee('Remove the avatar');

    expect($staff->refresh()->hasAvatar())->toBeFalse();
});

it('keeps the avatar when the removal is cancelled', function () {
    Storage::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);
    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('pam.png', 400, 400),
    )->execute();
    $this->actingAs($user->fresh());

    $page = visit(route('settings.profile.index'));

    $page->click('button:has-text("Remove the avatar")')
        ->assertVisible('[aria-labelledby="remove-avatar-title"]')
        ->assertSee('Remove your avatar?')
        ->click('[aria-labelledby="remove-avatar-title"] button:has-text("Keep it")')
        ->assertMissing('[aria-labelledby="remove-avatar-title"]')
        ->assertVisible('img#profile-avatar');

    expect($staff->refresh()->hasAvatar())->toBeTrue();
});

it('removes the avatar once the removal is confirmed', function () {
    Storage::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);
    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('pam.png', 400, 400),
    )->execute();
    $this->actingAs($user->fresh());

    $page = visit(route('settings.profile.index'));

    $page->click('button:has-text("Remove the avatar")')
        ->assertSee('Remove your avatar?')
        ->click('[aria-labelledby="remove-avatar-title"] button:has-text("Remove it")')
        ->assertMissing('img#profile-avatar')
        ->assertNoJavaScriptErrors();

    expect($staff->refresh()->hasAvatar())->toBeFalse();
});
