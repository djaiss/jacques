<?php

declare(strict_types=1);

namespace Tests\Feature\FaviconTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Testing\TestResponse;

it('ships the favicon as an icon file', function () {
    expect(file_get_contents(public_path('favicon.ico'), length: 4))->toBe("\x00\x00\x01\x00");
});

it('ships the favicon as a png image', function () {
    expect(file_get_contents(public_path('favicon-96x96.png'), length: 4))->toBe("\x89PNG");
});

it('ships the favicon as an svg image', function () {
    expect(file_get_contents(public_path('favicon.svg')))->toStartWith('<svg');
});

it('announces the favicons to a guest', function () {
    assertAnnouncesTheFavicons($this->get(route('auth.signIn.new')));
    assertAnnouncesTheFavicons($this->get('/'));
});

it('announces the favicons to a signed in person', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = $this->giveDefaultRole(User::factory()->create([
        'company_id' => $company->id,
    ]), Role::STAFF);

    $response = $this->actingAs($michael)->get(route('settings.index'));

    $response->assertStatus(200);
    assertAnnouncesTheFavicons($response);
});

function assertAnnouncesTheFavicons(TestResponse $response): void
{
    $png = asset('favicon-96x96.png');
    $svg = asset('favicon.svg');
    $ico = asset('favicon.ico');

    $response->assertSee("<link rel=\"icon\" type=\"image/png\" href=\"{$png}\" sizes=\"96x96\"", escape: false);
    $response->assertSee("<link rel=\"icon\" type=\"image/svg+xml\" href=\"{$svg}\"", escape: false);
    $response->assertSee("<link rel=\"shortcut icon\" href=\"{$ico}\"", escape: false);
}
