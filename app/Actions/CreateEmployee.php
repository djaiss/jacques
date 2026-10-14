<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;

/**
 * Create an employee of a company.
 */
class CreateEmployee
{
    private Employee $employee;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $firstName,
        private string $lastName,
        private ?string $employeeNumber = null,
        private ?string $displayName = null,
        private ?string $workEmail = null,
    ) {}

    public function execute(): Employee
    {
        $this->authorize();
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->employee;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::EmployeeCreate)
            ->forCompany($this->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
        $this->employeeNumber = TextSanitizer::nullablePlainText($this->employeeNumber);
        $this->displayName = TextSanitizer::nullablePlainText($this->displayName);

        $workEmail = TextSanitizer::nullablePlainText($this->workEmail);
        $this->workEmail = $workEmail === null ? null : mb_strtolower($workEmail);
    }

    private function create(): void
    {
        $this->employee = Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => $this->employeeNumber,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'display_name' => $this->displayName,
            'work_email' => $this->workEmail,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::EmployeeCreated,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
