<?php

declare(strict_types=1);

use App\Actions\UpdateStaffAvatar;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('uploads an avatar', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->post(route('settings.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('dwight.jpg', 400, 400),
    ]);

    $response->assertRedirect(route('settings.profile.index'));
    $response->assertSessionHas('status', 'Your avatar is saved.');

    $staff->refresh();

    expect($staff->hasAvatar())->toBeTrue();

    foreach (Staff::avatarPixelSizes() as $pixels) {
        Storage::disk('local')->assertExists($staff->avatarVariantPath($pixels));
    }
});

it('removes an avatar', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('pam.jpg', 400, 400),
    )->execute();

    $response = $this->actingAs($user->fresh())->delete(route('settings.avatar.destroy'));

    $response->assertRedirect(route('settings.profile.index'));
    $response->assertSessionHas('status', 'Your avatar is removed.');

    expect($staff->refresh()->hasAvatar())->toBeFalse();
});

it('refuses a file that is not an image', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->post(route('settings.avatar.update'), [
        'avatar' => UploadedFile::fake()->create('beets.pdf', 10, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('avatar');
});

it('refuses a file that is too large', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->post(route('settings.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('creed.jpg')->size(6 * 1024),
    ]);

    $response->assertSessionHasErrors('avatar');
});

it('redirects a visitor who is not signed in', function () {
    $response = $this->post(route('settings.avatar.update'));

    $response->assertRedirect(route('auth.signIn.new'));
});

it('serves a version of the avatar', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('jim.jpg', 400, 400),
    )->execute();

    $response = $this->actingAs($user->fresh())
        ->get(route('settings.avatar.show', ['staff' => $staff, 'size' => 192]));

    $response->assertOk();
    expect((string) $response->headers->get('content-type'))->toContain('image/webp');
});

it('serves the avatar of a staff member by their staff id', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $creed = Staff::factory()->create(['company_id' => $company->id, 'user_id' => null]);

    new UpdateStaffAvatar(
        author: $michael,
        staff: $creed,
        file: UploadedFile::fake()->image('creed.jpg', 400, 400),
    )->execute();

    $response = $this->actingAs($michael)
        ->get("/settings/account/profile/avatar/{$creed->id}/96");

    $response->assertOk();
    expect((string) $response->headers->get('content-type'))->toContain('image/webp');
});

it('does not serve the avatar of a staff member of another company', function () {
    Queue::fake();
    Storage::fake('local');

    $michael = $this->giveDefaultRole(User::factory()->create(), Role::OWNER);
    $nashua = Company::factory()->create();
    $nashuaOwner = $this->giveDefaultRole(User::factory()->create(['company_id' => $nashua->id]), Role::OWNER);
    $holly = Staff::factory()->create(['company_id' => $nashua->id, 'user_id' => null]);

    new UpdateStaffAvatar(
        author: $nashuaOwner,
        staff: $holly,
        file: UploadedFile::fake()->image('holly.jpg', 400, 400),
    )->execute();

    $response = $this->actingAs($michael)
        ->get(route('settings.avatar.show', ['staff' => $holly, 'size' => 96]));

    $response->assertNotFound();
});

it('does not serve the avatar of somebody at another company', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('holly.jpg', 400, 400),
    )->execute();

    $stranger = $this->giveDefaultRole(User::factory()->create(), Role::STAFF);

    $response = $this->actingAs($stranger)
        ->get(route('settings.avatar.show', ['staff' => $staff, 'size' => 96]));

    $response->assertNotFound();
});

it('does not serve the avatar to somebody who may not see the staff member', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('kelly.jpg', 400, 400),
    )->execute();

    $colleagueWithNoRole = User::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($colleagueWithNoRole)
        ->get(route('settings.avatar.show', ['staff' => $staff, 'size' => 96]));

    $response->assertNotFound();
});

it('serves only the sizes it writes to disk', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('toby.jpg', 400, 400),
    )->execute();

    $response = $this->actingAs($user->fresh())
        ->get(route('settings.avatar.show', ['staff' => $staff, 'size' => 999]));

    $response->assertNotFound();
});

it('shows the avatar at both densities on the profile screen', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Stanley',
        'last_name' => 'Hudson',
        'user_id' => $user->id,
    ]);
    $this->giveDefaultRole($user, Role::STAFF);

    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('stanley.jpg', 400, 400),
    )->execute();

    $response = $this->actingAs($user->fresh())->get(route('settings.profile.index'));

    $response->assertOk();
    $response->assertSee(route('settings.avatar.show', ['staff' => $staff, 'size' => 96]), escape: false);
    $response->assertSee(route('settings.avatar.show', ['staff' => $staff, 'size' => 192]), escape: false);
    $response->assertSee('Remove the avatar');
});

it('falls back to the initials when there is no avatar', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Stanley',
        'last_name' => 'Hudson',
        'user_id' => $user->id,
    ]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->get(route('settings.profile.index'));

    $response->assertOk();
    $response->assertSee('SH');
    $response->assertDontSee('Remove the avatar');
});
