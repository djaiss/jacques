<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ships_the_favicon_as_an_icon_file(): void
    {
        $this->assertSame("\x00\x00\x01\x00", file_get_contents(public_path('favicon.ico'), length: 4));
    }

    #[Test]
    public function it_ships_the_favicon_as_a_png_image(): void
    {
        $this->assertSame("\x89PNG", file_get_contents(public_path('favicon-96x96.png'), length: 4));
    }

    #[Test]
    public function it_ships_the_favicon_as_an_svg_image(): void
    {
        $this->assertStringStartsWith('<svg', file_get_contents(public_path('favicon.svg')));
    }

    #[Test]
    public function it_announces_the_favicons_to_a_guest(): void
    {
        $this->assertAnnouncesTheFavicons($this->get(route('auth.signIn.new')));
        $this->assertAnnouncesTheFavicons($this->get('/'));
    }

    #[Test]
    public function it_announces_the_favicons_to_a_signed_in_person(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $michael = $this->giveDefaultRole(User::factory()->create([
            'company_id' => $company->id,
        ]), Role::STAFF);

        $response = $this->actingAs($michael)->get(route('settings.index'));

        $response->assertStatus(200);
        $this->assertAnnouncesTheFavicons($response);
    }

    private function assertAnnouncesTheFavicons(TestResponse $response): void
    {
        $png = asset('favicon-96x96.png');
        $svg = asset('favicon.svg');
        $ico = asset('favicon.ico');

        $response->assertSee("<link rel=\"icon\" type=\"image/png\" href=\"{$png}\" sizes=\"96x96\"", escape: false);
        $response->assertSee("<link rel=\"icon\" type=\"image/svg+xml\" href=\"{$svg}\"", escape: false);
        $response->assertSee("<link rel=\"shortcut icon\" href=\"{$ico}\"", escape: false);
    }
}
