<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\CompleteOnboardingStepTest;

use App\Actions\CompleteOnboardingStep;
use App\Enums\OnboardingStepEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('moves the company to the next step', function () {
    Queue::fake();

    $user = owner();

    $result = new CompleteOnboardingStep(
        author: $user,
        step: OnboardingStepEnum::Location,
    )->execute();

    expect($result)->toBeInstanceOf(Company::class);
    $this->assertDatabaseHas('companies', [
        'id' => $user->company_id,
        'onboarding_step' => OnboardingStepEnum::Services->value,
        'is_onboarding_pending' => true,
    ]);
    Queue::assertNothingPushed();
});

it('never moves the company back', function () {
    Queue::fake();

    $user = owner();
    $user->company->update(['onboarding_step' => OnboardingStepEnum::Availability]);

    new CompleteOnboardingStep(
        author: $user,
        step: OnboardingStepEnum::Location,
    )->execute();

    expect($user->company->refresh()->onboarding_step)->toEqual(OnboardingStepEnum::Availability);
});

it('finishes the onboarding on the last step', function () {
    Queue::fake();

    $user = owner();
    $user->company->update(['onboarding_step' => OnboardingStepEnum::Availability]);

    new CompleteOnboardingStep(
        author: $user,
        step: OnboardingStepEnum::Availability,
    )->execute();

    expect($user->company->refresh()->is_onboarding_pending)->toBeFalse();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OnboardingCompleted
            && $job->company->id === $user->company_id
            && $job->user->id === $user->id,
    );
});

it('does nothing once the onboarding is finished', function () {
    Queue::fake();

    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->giveDefaultRole($user, Role::OWNER);

    new CompleteOnboardingStep(
        author: $user,
        step: OnboardingStepEnum::Availability,
    )->execute();

    $company->refresh();

    expect($company->is_onboarding_pending)->toBeFalse();
    expect($company->onboarding_step)->toEqual(OnboardingStepEnum::Location);
    Queue::assertNothingPushed();
});

it('refuses a user who cannot manage the company', function () {
    $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);

    new CompleteOnboardingStep(
        author: $user,
        step: OnboardingStepEnum::Location,
    )->execute();
})->throws(ModelNotFoundException::class);

it('offers every service at the headquarters when the services step is completed', function () {
    Queue::fake();

    $user = owner();
    $scranton = Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => true]);
    $stamford = Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => false]);
    $haircut = Service::factory()->create(['company_id' => $user->company_id]);

    new CompleteOnboardingStep(author: $user, step: OnboardingStepEnum::Services)->execute();
    new CompleteOnboardingStep(author: $user, step: OnboardingStepEnum::Services)->execute();

    expect(LocationService::query()->where('location_id', $scranton->id)->where('service_id', $haircut->id)->count())->toEqual(1);
    $this->assertDatabaseMissing('location_services', ['location_id' => $stamford->id]);
});

it('puts the owner on the team at the headquarters performing every service', function () {
    Queue::fake();

    $user = owner();
    $michael = Staff::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
    $scranton = Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => true]);
    Service::factory()->count(2)->create(['company_id' => $user->company_id]);

    new CompleteOnboardingStep(author: $user, step: OnboardingStepEnum::Services)->execute();

    expect($michael->activeLocations()->whereKey($scranton->id)->exists())->toBeTrue();
    expect($michael->locationServices()->count())->toEqual(2);
});

it('gives the owner the services offered since the first step', function () {
    Queue::fake();

    $user = owner();
    $michael = Staff::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
    $scranton = Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => true]);
    $haircut = Service::factory()->create(['company_id' => $user->company_id]);
    LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $haircut->id]);

    new CompleteOnboardingStep(author: $user, step: OnboardingStepEnum::Services)->execute();

    expect($michael->locationServices()->pluck('service_id')->all())->toEqual([$haircut->id]);
});

it('does not give the owner back a service they took off', function () {
    Queue::fake();

    $user = owner();
    $michael = Staff::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
    Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => true]);
    $haircut = Service::factory()->create(['company_id' => $user->company_id]);
    $beardTrim = Service::factory()->create(['company_id' => $user->company_id]);

    new CompleteOnboardingStep(author: $user, step: OnboardingStepEnum::Services)->execute();
    $michael->locationServices()->detach(LocationService::query()->where('service_id', $haircut->id)->value('id'));
    $balayage = Service::factory()->create(['company_id' => $user->company_id]);
    new CompleteOnboardingStep(author: $user, step: OnboardingStepEnum::Services)->execute();

    expect($michael->locationServices()->pluck('service_id')->all())->toEqualCanonicalizing([$beardTrim->id, $balayage->id]);
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);

    return test()->giveDefaultRole($user, Role::OWNER);
}
