<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Delete a staff member for good, with their location assignments, the
 * services they performed, their working hours, their absences and their
 * avatar. The account they signed in with, if any, stays. Somebody who leaves
 * is deactivated instead; deleting is for a record created by mistake, and is
 * refused once an appointment mentions them.
 */
class DestroyStaff
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
    ) {}

    public function execute(): void
    {
        $this->authorize();
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::StaffDelete)
            ->forCompany($this->staff->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->staff->appointments()->exists()) {
            throw ValidationException::withMessages([
                'staff' => __('This person has appointments and cannot be deleted. Deactivate them instead.'),
            ]);
        }
    }

    private function destroy(): void
    {
        if ($this->staff->hasAvatar()) {
            $disk = Storage::disk((string) config('filesystems.default'));

            foreach (Staff::avatarPixelSizes() as $pixels) {
                $disk->delete($this->staff->avatarVariantPath($pixels));
            }
        }

        $this->staff->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffDeleted,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}
