<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create a company, its first user and the employee that user is. The company
 * gets the roles it starts life with, and the first user is given the Owner
 * role, which is what owning the company means.
 */
class CreateCompany
{
    private Company $company;

    private User $owner;

    private Employee $employee;

    public function __construct(
        private string $name,
        private string $firstName,
        private string $lastName,
        private string $email,
        private readonly string $password,
    ) {}

    public function execute(): Company
    {
        $this->sanitize();

        DB::transaction(function (): void {
            $this->createCompany();
            $this->createOwner();
            $this->createDefaultRoles();
            $this->giveOwnerRole();
            $this->createEmployee();
            $this->attachEmployee();
        });

        $this->log();

        return $this->company;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
        $this->email = mb_strtolower(TextSanitizer::plainText($this->email));
    }

    private function createCompany(): void
    {
        $this->company = Company::query()->create([
            'name' => $this->name,
            'slug' => $this->slug(),
            'trial_ends_at' => now()->addDays(30),
        ]);
    }

    private function createOwner(): void
    {
        $this->owner = new CreateUser(
            company: $this->company,
            email: $this->email,
            password: $this->password,
        )->execute();
    }

    private function createDefaultRoles(): void
    {
        new CreateDefaultRoles(company: $this->company)->execute();
    }

    private function giveOwnerRole(): void
    {
        $owner = $this->company->roles()
            ->where('slug', Role::OWNER)
            ->firstOrFail();

        $this->owner->roles()->attach($owner->id);
        $this->owner->forgetGrants();
    }

    private function createEmployee(): void
    {
        $this->employee = new CreateEmployee(
            author: $this->owner,
            company: $this->company,
            firstName: $this->firstName,
            lastName: $this->lastName,
            workEmail: $this->email,
        )->execute();
    }

    private function attachEmployee(): void
    {
        $this->owner->employee_id = $this->employee->id;
        $this->owner->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->owner,
            action: UserActionEnum::CompanyCreated,
            parameters: ['name' => $this->company->name],
        )->onQueue('low');
    }

    private function slug(): string
    {
        $base = Str::slug($this->name);
        $base = $base === '' ? 'company' : $base;

        $slug = $base;
        $suffix = 1;

        while (Company::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
