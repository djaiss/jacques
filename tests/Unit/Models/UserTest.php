<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\EmailSent;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

it('belongs to a company', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);

    expect($user->company()->exists())->toBeTrue();
    expect($user->company->name)->toEqual('Dunder Mifflin');
});

it('has one staff record', function () {
    $user = User::factory()->create();
    Staff::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'display_name' => null,
    ]);

    expect($user->staff()->exists())->toBeTrue();
    expect($user->staff->name)->toEqual('Michael Scott');
});

it('has no staff record by default', function () {
    $user = User::factory()->create();

    expect($user->staff()->exists())->toBeFalse();
    expect($user->staff)->toBeNull();
});

it('knows whether it holds the owner role', function () {
    $michael = User::factory()->create();
    $dwight = User::factory()->create(['company_id' => $michael->company_id]);
    $owner = Role::factory()->create(['company_id' => $michael->company_id, 'slug' => Role::OWNER]);
    $michael->roles()->attach($owner);

    expect($michael->isOwner())->toBeTrue();
    expect($dwight->isOwner())->toBeFalse();
});

it('has many emails sent', function () {
    $user = User::factory()->create();
    EmailSent::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'subject' => 'Somebody signed in from a new place',
    ]);

    expect($user->emailsSent()->exists())->toBeTrue();
    expect($user->emailsSent->first()->subject)->toEqual('Somebody signed in from a new place');
});

it('returns the password hash as the authentication password', function () {
    $user = User::factory()->create(['password_hash' => Hash::make('dwightschrute')]);

    expect($user->getAuthPassword())->toEqual($user->password_hash);
    expect(Hash::check('dwightschrute', $user->getAuthPassword()))->toBeTrue();
});

it('returns an empty authentication password when the user signs in through sso', function () {
    $user = User::factory()->singleSignOn()->create();

    expect($user->getAuthPassword())->toEqual('');
});

it('knows whether it uses single sign on', function () {
    $withSso = User::factory()->singleSignOn()->create();
    $withPassword = User::factory()->create();

    expect($withSso->usesSingleSignOn())->toBeTrue();
    expect($withPassword->usesSingleSignOn())->toBeFalse();
});

it('hides the password hash when serialized', function () {
    $user = User::factory()->create();

    expect($user->toArray())->not->toHaveKey('password_hash');
    expect($user->toArray())->not->toHaveKey('remember_token');
});

it('casts the dates and the active flag', function () {
    $user = User::factory()->inactive()->create([
        'last_signed_in_at' => now(),
        'password_changed_at' => '2026-07-31 09:30:00',
    ]);

    $user->refresh();

    expect($user->is_active)->toBeFalse();
    expect($user->last_signed_in_at)->not->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->password_changed_at)->toBeInstanceOf(Carbon::class);
});

it('has no password change date when the password was never set', function () {
    $user = User::factory()->singleSignOn()->create();

    expect($user->password_changed_at)->toBeNull();
});

it('soft deletes', function () {
    $user = User::factory()->create();

    $user->delete();

    $this->assertSoftDeleted($user);
});

it('knows whether the email address was confirmed', function () {
    expect(User::factory()->unverified()->create()->hasConfirmedEmail())->toBeFalse();
    expect(User::factory()->create()->hasConfirmedEmail())->toBeTrue();
});
