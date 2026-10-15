<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Create a staff member of a company, last in the order of the team, and link
 * them to an account of the same company when one is given. An account gives
 * access to one staff member at most.
 */
class CreateStaff
{
    private Staff $staff;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $firstName,
        private ?string $lastName = null,
        private ?string $displayName = null,
        private ?string $email = null,
        private ?string $phone = null,
        private ?string $jobTitle = null,
        private ?string $bio = null,
        private readonly bool $isBookable = true,
        private readonly ?User $user = null,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->validate();
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->staff;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::StaffCreate)
            ->forCompany($this->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->user === null) {
            return;
        }

        if ($this->user->company_id !== $this->company->id) {
            throw new ModelNotFoundException('User not found');
        }

        if (Staff::query()->where('user_id', $this->user->id)->exists()) {
            throw new InvalidArgumentException('The account already gives access to another staff member');
        }
    }

    private function sanitize(): void
    {
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::nullablePlainText($this->lastName);
        $this->displayName = TextSanitizer::nullablePlainText($this->displayName);
        $this->phone = TextSanitizer::nullablePlainText($this->phone);
        $this->jobTitle = TextSanitizer::nullablePlainText($this->jobTitle);
        $this->bio = TextSanitizer::nullablePlainText($this->bio);

        $email = TextSanitizer::nullablePlainText($this->email);
        $this->email = $email === null ? null : mb_strtolower($email);
    }

    private function create(): void
    {
        $lastPosition = Staff::query()
            ->where('company_id', $this->company->id)
            ->max('position');

        $this->staff = Staff::query()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user?->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'display_name' => $this->displayName,
            'email' => $this->email,
            'phone' => $this->phone,
            'job_title' => $this->jobTitle,
            'bio' => $this->bio,
            'is_bookable' => $this->isBookable,
            'position' => (int) $lastPosition + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::StaffCreated,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}
