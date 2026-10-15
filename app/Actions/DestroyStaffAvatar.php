<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Remove the avatar of a staff member, falling them back to their initials.
 */
class DestroyStaffAvatar
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();

        $path = $this->staff->avatar_path;

        if ($path === null) {
            return $this->staff;
        }

        $this->staff->avatar_path = null;
        $this->staff->save();

        // The files go last: deleting one cannot be undone, and a record that
        // points at nothing is a smaller problem than a file nothing points at.
        foreach (Staff::avatarPixelSizes() as $pixels) {
            $this->disk()->delete($path.'_'.$pixels.'.webp');
        }

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

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('filesystems.default'));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->author->company,
            user: $this->author,
            action: UserActionEnum::StaffAvatarDeleted,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}
