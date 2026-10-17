<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;
use InvalidArgumentException;

/**
 * Update the information a staff member shows their colleagues and clients.
 * Who may do it to whom comes from the roles of the author: a member gets
 * their own record and nobody else's, while somebody who looks after people
 * gets everybody. An email address belongs to one staff member of the company
 * at most. Removing it drops the invitation prepared for that address, and
 * changing it stops the link already sent to the old one, so the invitation
 * is prepared again for the new address.
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
        $this->validateEmail();
        $this->resetInvitation();
        $this->update();
        $this->forgetInvitation();
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

    private function validateEmail(): void
    {
        if ($this->email === null) {
            return;
        }

        $taken = Staff::query()
            ->where('company_id', $this->staff->company_id)
            ->where('email', $this->email)
            ->whereKeyNot($this->staff->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('Another staff member of the company already uses this email address');
        }
    }

    private function resetInvitation(): void
    {
        if ($this->email === null || $this->email === $this->staff->email) {
            return;
        }

        $this->staff->invitation()->update([
            'token_hash' => null,
            'sent_at' => null,
            'expires_at' => null,
        ]);
    }

    private function forgetInvitation(): void
    {
        if ($this->email !== null) {
            return;
        }

        $this->staff->invitation()->delete();
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
