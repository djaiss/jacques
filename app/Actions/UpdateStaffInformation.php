<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;

/**
 * Update the information a staff member shows their colleagues and clients.
 * Who may do it to whom comes from the roles of the author: a member gets
 * their own record and nobody else's, while somebody who looks after people
 * gets everybody.
 */
class UpdateStaffInformation
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private string $firstName,
        private ?string $lastName,
        private ?string $displayName,
        private ?string $email,
        private ?string $phone,
        private ?string $jobTitle,
        private ?string $bio,
        private readonly bool $isBookable,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->sanitize();
        $this->update();
        $this->log();

        return $this->staff;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::StaffUpdate)
            ->forStaff($this->staff)
            ->authorize();
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

    private function update(): void
    {
        $this->staff->first_name = $this->firstName;
        $this->staff->last_name = $this->lastName;
        $this->staff->display_name = $this->displayName;
        $this->staff->email = $this->email;
        $this->staff->phone = $this->phone;
        $this->staff->job_title = $this->jobTitle;
        $this->staff->bio = $this->bio;
        $this->staff->is_bookable = $this->isBookable;
        $this->staff->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->author->company,
            user: $this->author,
            action: UserActionEnum::StaffInformationUpdated,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}
