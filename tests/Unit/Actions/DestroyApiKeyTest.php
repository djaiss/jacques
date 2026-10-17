<?php

declare(strict_types=1);

use App\Actions\DestroyApiKey;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('revokes the key', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $apiKey = $user->createToken('Dundie awards bot')->accessToken;

    new DestroyApiKey(user: $user, apiKeyId: $apiKey->id)->execute();

    $this->assertModelMissing($apiKey);
    expect($user->tokens()->count())->toBe(0);
});

it('refuses a key that belongs to somebody else', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $dwight = User::factory()->create(['company_id' => $company->id]);
    $apiKey = $dwight->createToken('Beet farm sync')->accessToken;

    try {
        new DestroyApiKey(user: $michael, apiKeyId: $apiKey->id)->execute();
    } finally {
        $this->assertModelExists($apiKey);
    }
})->throws(ModelNotFoundException::class);

it('logs the revocation with the name the key had', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $apiKey = $user->createToken('Dundie awards bot')->accessToken;

    new DestroyApiKey(user: $user, apiKeyId: $apiKey->id)->execute();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ApiKeyDeleted
            && $job->company->id === $company->id
            && $job->user->id === $user->id
            && $job->parameters === ['name' => 'Dundie awards bot'],
    );
});
