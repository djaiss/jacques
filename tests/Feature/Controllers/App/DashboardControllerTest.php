<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_dashboard(): void
    {
        $michael = User::factory()->create();

        $response = $this->actingAs($michael)->get(route('dashboard.index'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    #[Test]
    public function it_sends_a_guest_to_sign_in(): void
    {
        $this->get(route('dashboard.index'))->assertRedirect(route('auth.signIn.new'));
    }
}
