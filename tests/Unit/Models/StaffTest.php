<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\LocationStaff;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;

it('belongs to a company', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $staff = Staff::factory()->create(['company_id' => $company->id]);

    expect($staff->company()->exists())->toBeTrue();
    expect($staff->company->name)->toEqual('Dunder Mifflin');
});

it('has many appointments', function () {
    $jim = Staff::factory()->create();
    Appointment::factory()->create(['company_id' => $jim->company_id, 'staff_id' => $jim->id]);

    expect($jim->appointments()->exists())->toBeTrue();
});

it('belongs to a user', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    $staff = Staff::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    expect($staff->user()->exists())->toBeTrue();
    expect($staff->user->email)->toEqual('michael.scott@dundermifflin.com');
});

it('has no user when the staff member has no account', function () {
    $staff = Staff::factory()->create();

    expect($staff->user()->exists())->toBeFalse();
});

it('belongs to many locations with the state of each assignment', function () {
    $staff = Staff::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $staff->company_id]);
    $stamford = Location::factory()->create(['company_id' => $staff->company_id]);
    LocationStaff::factory()->create(['location_id' => $scranton->id, 'staff_id' => $staff->id]);
    LocationStaff::factory()->paused()->create(['location_id' => $stamford->id, 'staff_id' => $staff->id]);

    $locations = $staff->locations()->get()->keyBy('id');

    expect($staff->locations()->exists())->toBeTrue();
    expect($locations)->toHaveCount(2);
    expect((bool) $locations[$scranton->id]->pivot->is_active)->toBeTrue();
    expect((bool) $locations[$stamford->id]->pivot->is_active)->toBeFalse();
});

it('leaves paused assignments out of the active locations', function () {
    $staff = Staff::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $staff->company_id]);
    $stamford = Location::factory()->create(['company_id' => $staff->company_id]);
    LocationStaff::factory()->create(['location_id' => $scranton->id, 'staff_id' => $staff->id]);
    LocationStaff::factory()->paused()->create(['location_id' => $stamford->id, 'staff_id' => $staff->id]);

    expect($staff->activeLocations()->pluck('locations.id')->all())->toEqual([$scranton->id]);
});

it('belongs to many location services', function () {
    $staff = Staff::factory()->create();
    $locationService = LocationService::factory()->create();
    $staff->locationServices()->attach($locationService);

    expect($staff->locationServices()->exists())->toBeTrue();
    expect($staff->locationServices()->pluck('location_services.id')->all())->toEqual([$locationService->id]);
});

it('has one invitation', function () {
    $staff = Staff::factory()->create();
    StaffInvitation::factory()->create(['staff_id' => $staff->id]);

    expect($staff->invitation()->exists())->toBeTrue();
});

it('returns the legal name as the name when the staff member goes by no other name', function () {
    $staff = Staff::factory()->create([
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'display_name' => null,
    ]);

    expect($staff->name)->toEqual('Michael Scott');
});

it('returns the display name as the name when the staff member goes by another name', function () {
    $staff = Staff::factory()->create([
        'first_name' => 'Dwight',
        'last_name' => 'Schrute',
        'display_name' => 'Assistant to the Regional Manager',
    ]);

    expect($staff->name)->toEqual('Assistant to the Regional Manager');
});

it('returns the full name', function () {
    $staff = Staff::factory()->create([
        'first_name' => 'Pam',
        'last_name' => 'Beesly',
        'display_name' => 'Pamela',
    ]);

    expect($staff->full_name)->toEqual('Pam Beesly');
});

it('returns the first name alone as the full name when there is no last name', function () {
    $staff = Staff::factory()->create([
        'first_name' => 'Creed',
        'last_name' => null,
        'display_name' => null,
    ]);

    expect($staff->full_name)->toEqual('Creed');
    expect($staff->name)->toEqual('Creed');
});

it('has an avatar when a path was stored', function () {
    $staff = Staff::factory()->create(['avatar_path' => 'avatars/1/stanley']);

    expect($staff->hasAvatar())->toBeTrue();
});

it('has no avatar by default', function () {
    $staff = Staff::factory()->create();

    expect($staff->hasAvatar())->toBeFalse();
});

it('names each version of the avatar after its size', function () {
    $staff = Staff::factory()->create(['avatar_path' => 'avatars/1/stanley']);

    expect($staff->avatarVariantPath(96))->toEqual('avatars/1/stanley_96.webp');
    expect($staff->avatarVariantPath(192))->toEqual('avatars/1/stanley_192.webp');
});

it('writes the displayed size and twice it', function () {
    expect(Staff::avatarPixelSizes())->toEqual([96, 192]);
});
