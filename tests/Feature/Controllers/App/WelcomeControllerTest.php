<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WelcomeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_welcome_screen_to_a_new_company(): void
    {
        $user = $this->newcomer();

        $response = $this->actingAs($user)->get(route('welcome.index'));

        $response->assertStatus(200);
        $response->assertSee('Hi Michael, welcome to Jacques.');
        $response->assertSee('Creator of Jacques');
        $response->assertSee(route('welcome.update'));
        $response->assertSee('data-turbo="true"', false);
    }

    #[Test]
    public function it_sends_a_company_already_welcomed_to_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('welcome.index'));

        $response->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_dismisses_the_welcome_screen(): void
    {
        Queue::fake();

        $user = $this->newcomer();

        $response = $this->actingAs($user)->put(route('welcome.update'));

        $response->assertRedirect(route('onboarding.index'));
        $this->assertFalse($user->company->refresh()->is_welcome_screen_pending);
    }

    #[Test]
    public function it_does_not_show_the_welcome_screen_again_once_dismissed(): void
    {
        Queue::fake();

        $user = $this->newcomer();

        $this->actingAs($user)->put(route('welcome.update'));

        $this->actingAs($user)->get(route('welcome.index'))->assertRedirect(route('home.index'));
        $this->actingAs($user)->get(route('home.index'))->assertStatus(200);
    }

    #[Test]
    public function it_sends_a_new_company_to_the_welcome_screen_from_any_signed_in_screen(): void
    {
        $user = $this->newcomer();

        $this->actingAs($user)->get(route('home.index'))->assertRedirect(route('welcome.index'));
        $this->actingAs($user)->get(route('settings.index'))->assertRedirect(route('welcome.index'));
        $this->actingAs($user)->get(route('settings.profile.index'))->assertRedirect(route('welcome.index'));
        $this->actingAs($user)->get(route('settings.roles.index'))->assertRedirect(route('welcome.index'));
    }

    #[Test]
    public function it_lets_a_new_company_see_the_email_confirmation_notice(): void
    {
        $user = $this->newcomer(['email_verified_at' => null]);

        $response = $this->actingAs($user)->get(route('auth.verification.notice'));

        $response->assertStatus(200);
    }

    #[Test]
    public function it_lets_a_guest_reach_home(): void
    {
        $response = $this->get(route('home.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function it_sends_a_guest_to_sign_in(): void
    {
        $this->get(route('welcome.index'))->assertRedirect(route('auth.signIn.new'));
        $this->put(route('welcome.update'))->assertRedirect(route('auth.signIn.new'));
    }

    /** @param  array<string, mixed>  $attributes */
    private function newcomer(array $attributes = []): User
    {
        $company = Company::factory()->welcomeScreenPending()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        return User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            ...$attributes,
        ]);
    }
}
