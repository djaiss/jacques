<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('creates an api key', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
        'name' => 'Dundie awards bot',
    ]);

    $response->assertRedirect(route('settings.security.index'));
    $response->assertSessionHas('status', 'Your API key is ready.');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'Dundie awards bot',
    ]);
});

it('hands the key over once', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
        'name' => 'Dundie awards bot',
    ]);

    $response->assertSessionHas('apiKey');

    $this->actingAs($user)->get(route('settings.security.index'))
        ->assertStatus(200)
        ->assertSee('Your new API key', escape: false);

    $this->actingAs($user)->get(route('settings.security.index'))
        ->assertDontSee('Your new API key', escape: false);
});

it('refuses a name that is too short', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
        'name' => 'ab',
    ]);

    $response->assertSessionHasErrors('name');

    expect($user->tokens()->count())->toBe(0);
});

it('refuses a missing name', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), []);

    $response->assertSessionHasErrors('name');
});

it('revokes an api key', function () {
    Queue::fake();

    $user = User::factory()->create();
    $apiKey = $user->createToken('Dundie awards bot')->accessToken;

    $response = $this->actingAs($user)->delete(route('settings.apiKeys.destroy', $apiKey->id));

    $response->assertRedirect(route('settings.security.index'));
    $response->assertSessionHas('status', 'The API key is revoked.');

    $this->assertModelMissing($apiKey);
});

it('refuses to revoke a key that belongs to somebody else', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $dwight = User::factory()->create(['company_id' => $company->id]);
    $apiKey = $dwight->createToken('Beet farm sync')->accessToken;

    $response = $this->actingAs($michael)->delete(route('settings.apiKeys.destroy', $apiKey->id));

    $response->assertNotFound();

    $this->assertModelExists($apiKey);
});

it('refuses a visitor who is not signed in', function () {
    $this->post(route('settings.apiKeys.create'), ['name' => 'Dundie awards bot'])
        ->assertRedirect(route('auth.signIn.new'));

    $this->delete(route('settings.apiKeys.destroy', 1))
        ->assertRedirect(route('auth.signIn.new'));
});
