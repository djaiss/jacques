<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Profile;

use App\Actions\DestroyStaffAvatar;
use App\Actions\UpdateStaffAvatar;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $staff = $request->user()->staff;

        if ($staff === null) {
            abort(404);
        }

        new UpdateStaffAvatar(
            author: $request->user(),
            staff: $staff,
            file: $validated['avatar'],
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your avatar is saved.'))
            ->with('status_description', __('It may take a minute to appear everywhere.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $staff = $request->user()->staff;

        if ($staff === null) {
            abort(404);
        }

        new DestroyStaffAvatar(
            author: $request->user(),
            staff: $staff,
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your avatar is removed.'))
            ->with('status_description', __('We show your initials again.'));
    }

    public function show(Request $request, Staff $staff, int $size): StreamedResponse
    {
        $request->user()
            ->permission(PermissionEnum::StaffView)
            ->forStaff($staff)
            ->authorize();

        if (! $staff->hasAvatar()) {
            abort(404);
        }

        // Only the sizes actually written to disk, so this cannot be turned
        // into an endpoint that resizes whatever it is asked for.
        if (! in_array($size, Staff::avatarPixelSizes(), true)) {
            abort(404);
        }

        $disk = Storage::disk((string) config('filesystems.default'));
        $path = $staff->avatarVariantPath($size);

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path, headers: [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
