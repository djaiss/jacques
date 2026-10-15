<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ships_a_favicon_that_is_an_icon_file(): void
    {
        $path = public_path('favicon.ico');

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
        $this->assertSame("\x00\x00\x01\x00", file_get_contents($path, length: 4));
    }

    #[Test]
    public function it_announces_the_favicon_to_a_guest(): void
    {
        $this->get(route('auth.signIn.new'))->assertSee('rel="icon"', escape: false);
        $this->get('/')->assertSee('rel="icon"', escape: false);
    }

    #[Test]
    public function it_announces_the_favicon_to_a_signed_in_person(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $michael = $this->giveDefaultRole(User::factory()->create([
            'company_id' => $company->id,
        ]), Role::STAFF);

        $response = $this->actingAs($michael)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('rel="icon"', escape: false);
    }
}
