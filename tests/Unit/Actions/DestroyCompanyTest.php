<?php

declare(strict_types=1);

use App\Actions\DestroyCompany;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('destroys the company', function () {
    $company = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    new DestroyCompany(
        user: $owner,
        company: $company,
    )->execute();

    $this->assertModelMissing($company);
});

it('throws when the user is not the owner', function () {
    $company = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $member = User::factory()->create(['company_id' => $company->id]);

    new DestroyCompany(
        user: $member,
        company: $company,
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the user belongs to another company', function () {
    $company = Company::factory()->create();
    $stranger = User::factory()->create();

    new DestroyCompany(
        user: $stranger,
        company: $company,
    )->execute();
})->throws(ModelNotFoundException::class);
