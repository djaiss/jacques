<?php

declare(strict_types=1);

use App\Actions\CreateApiKey;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('mints a key for the user', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $key = new CreateApiKey(user: $user, name: 'Dundie awards bot')->execute();

    expect($key)->toBeString();
    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->name)->toBe('Dundie awards bot');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'Dundie awards bot',
    ]);
});

it('returns the key in plain text and stores only its hash', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $key = new CreateApiKey(user: $user, name: 'Deployment script')->execute();

    expect($key)->toContain('|');

    $stored = $user->tokens()->first()->token;

    expect($stored)->not->toBe($key);
    expect($stored)->toBe(hash('sha256', explode('|', $key)[1]));
});

it('leaves a key unused until something uses it', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    new CreateApiKey(user: $user, name: 'Deployment script')->execute();

    expect($user->tokens()->first()->last_used_at)->toBeNull();
});

it('logs the creation', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    new CreateApiKey(user: $user, name: 'Dundie awards bot')->execute();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ApiKeyCreated
            && $job->company->id === $company->id
            && $job->user->id === $user->id
            && $job->parameters === ['name' => 'Dundie awards bot'],
    );
});
