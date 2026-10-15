<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Set the avatar a staff member shows their colleagues. Two square versions are
 * written, one at the size the app displays and one at twice it, so a dense
 * screen has a sharp one to pick from.
 */
class UpdateStaffAvatar
{
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const int MAX_SIZE_IN_BYTES = 5 * 1024 * 1024;

    private ?string $previousPath = null;

    private string $path;

    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly UploadedFile $file,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->validate();
        $this->store();
        $this->save();
        $this->removePrevious();
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

    private function validate(): void
    {
        if (! in_array($this->file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('The file must be a jpeg, png or webp image');
        }

        if ($this->file->getSize() > self::MAX_SIZE_IN_BYTES) {
            throw new InvalidArgumentException('The file must not be larger than 5 MB');
        }
    }

    private function store(): void
    {
        $this->previousPath = $this->staff->avatar_path;

        $directory = 'avatars/'.$this->staff->id;
        $stem = Str::uuid()->toString();

        foreach (Staff::avatarPixelSizes() as $pixels) {
            new ResizeImage(
                file: $this->file,
                width: $pixels,
                height: $pixels,
                path: $directory,
                name: $stem.'_'.$pixels.'.webp',
                disk: $this->diskName(),
            )->execute();
        }

        $this->path = $directory.'/'.$stem;
    }

    private function save(): void
    {
        $this->staff->avatar_path = $this->path;
        $this->staff->save();
    }

    private function removePrevious(): void
    {
        if ($this->previousPath === null) {
            return;
        }

        foreach (Staff::avatarPixelSizes() as $pixels) {
            $this->disk()->delete($this->previousPath.'_'.$pixels.'.webp');
        }
    }

    private function diskName(): string
    {
        return (string) config('filesystems.default');
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->author->company,
            user: $this->author,
            action: UserActionEnum::StaffAvatarUpdated,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}
