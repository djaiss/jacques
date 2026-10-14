<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Employee;
use App\Models\User;

/**
 * Update the information an employee shows their colleagues. Who may do it to
 * whom comes from the roles of the author: a member gets their own record and
 * nobody else's, while somebody who looks after people gets everybody.
 */
class UpdateEmployeeInformation
{
    public function __construct(
        private readonly User $author,
        private readonly Employee $employee,
        private string $firstName,
        private string $lastName,
        private ?string $displayName = null,
        private ?string $workEmail = null,
    ) {}

    public function execute(): Employee
    {
        $this->authorize();
        $this->sanitize();
        $this->update();
        $this->log();

        return $this->employee;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::EmployeeUpdate)
            ->forEmployee($this->employee)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
        $this->displayName = TextSanitizer::nullablePlainText($this->displayName);

        $workEmail = TextSanitizer::nullablePlainText($this->workEmail);
        $this->workEmail = $workEmail === null ? null : mb_strtolower($workEmail);
    }

    private function update(): void
    {
        $this->employee->first_name = $this->firstName;
        $this->employee->last_name = $this->lastName;
        $this->employee->display_name = $this->displayName;
        $this->employee->work_email = $this->workEmail;
        $this->employee->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->author->company,
            user: $this->author,
            action: UserActionEnum::EmployeeInformationUpdated,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
