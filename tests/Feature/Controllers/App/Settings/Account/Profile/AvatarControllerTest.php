<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Profile;

use App\Actions\UpdateEmployeeAvatar;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvatarControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_uploads_an_avatar(): void
    {
        Queue::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        $response = $this->actingAs($user)->post(route('settings.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('dwight.jpg', 400, 400),
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status', 'Your avatar is saved.');

        $employee->refresh();

        $this->assertTrue($employee->hasAvatar());

        foreach (Employee::avatarPixelSizes() as $pixels) {
            Storage::disk('local')->assertExists($employee->avatarVariantPath($pixels));
        }
    }

    #[Test]
    public function it_removes_an_avatar(): void
    {
        Queue::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        new UpdateEmployeeAvatar(
            author: $user,
            employee: $employee,
            file: UploadedFile::fake()->image('pam.jpg', 400, 400),
        )->execute();

        $response = $this->actingAs($user->fresh())->delete(route('settings.avatar.destroy'));

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status', 'Your avatar is removed.');

        $this->assertFalse($employee->refresh()->hasAvatar());
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_an_image(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        $response = $this->actingAs($user)->post(route('settings.avatar.update'), [
            'avatar' => UploadedFile::fake()->create('beets.pdf', 10, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    #[Test]
    public function it_refuses_a_file_that_is_too_large(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        $response = $this->actingAs($user)->post(route('settings.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('creed.jpg')->size(6 * 1024),
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->post(route('settings.avatar.update'));

        $response->assertRedirect(route('auth.signIn.new'));
    }

    #[Test]
    public function it_serves_a_version_of_the_avatar(): void
    {
        Queue::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        new UpdateEmployeeAvatar(
            author: $user,
            employee: $employee,
            file: UploadedFile::fake()->image('jim.jpg', 400, 400),
        )->execute();

        $response = $this->actingAs($user->fresh())
            ->get(route('settings.avatar.show', ['employee' => $employee, 'size' => 192]));

        $response->assertOk();
        $this->assertStringContainsString('image/webp', (string) $response->headers->get('content-type'));
    }

    #[Test]
    public function it_does_not_serve_the_avatar_of_somebody_at_another_company(): void
    {
        Queue::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        new UpdateEmployeeAvatar(
            author: $user,
            employee: $employee,
            file: UploadedFile::fake()->image('holly.jpg', 400, 400),
        )->execute();

        $stranger = $this->giveDefaultRole(User::factory()->create(), Role::STAFF);

        $response = $this->actingAs($stranger)
            ->get(route('settings.avatar.show', ['employee' => $employee, 'size' => 96]));

        $response->assertNotFound();
    }

    #[Test]
    public function it_does_not_serve_the_avatar_to_somebody_who_may_not_see_the_employee(): void
    {
        Queue::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        new UpdateEmployeeAvatar(
            author: $user,
            employee: $employee,
            file: UploadedFile::fake()->image('kelly.jpg', 400, 400),
        )->execute();

        $colleagueWithNoRole = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($colleagueWithNoRole)
            ->get(route('settings.avatar.show', ['employee' => $employee, 'size' => 96]));

        $response->assertNotFound();
    }

    #[Test]
    public function it_serves_only_the_sizes_it_writes_to_disk(): void
    {
        Queue::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        new UpdateEmployeeAvatar(
            author: $user,
            employee: $employee,
            file: UploadedFile::fake()->image('toby.jpg', 400, 400),
        )->execute();

        $response = $this->actingAs($user->fresh())
            ->get(route('settings.avatar.show', ['employee' => $employee, 'size' => 999]));

        $response->assertNotFound();
    }

    #[Test]
    public function it_shows_the_avatar_at_both_densities_on_the_profile_screen(): void
    {
        Queue::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        new UpdateEmployeeAvatar(
            author: $user,
            employee: $employee,
            file: UploadedFile::fake()->image('stanley.jpg', 400, 400),
        )->execute();

        $response = $this->actingAs($user->fresh())->get(route('settings.profile.index'));

        $response->assertOk();
        $response->assertSee(route('settings.avatar.show', ['employee' => $employee, 'size' => 96]), escape: false);
        $response->assertSee(route('settings.avatar.show', ['employee' => $employee, 'size' => 192]), escape: false);
        $response->assertSee('Remove the avatar');
    }

    #[Test]
    public function it_falls_back_to_the_initials_when_there_is_no_avatar(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->giveDefaultRole($user, Role::STAFF);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertOk();
        $response->assertSee('SH');
        $response->assertDontSee('Remove the avatar');
    }
}
