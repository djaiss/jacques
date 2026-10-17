<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding;

use App\Actions\SendStaffInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CompletionInvitationController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $staff = $request->user()->company->staff()
            ->whereHas('invitation', fn ($query) => $query->whereNull('sent_at')->whereNull('revoked_at')->whereNull('accepted_at'))
            ->get();

        $sent = 0;

        foreach ($staff as $member) {
            try {
                new SendStaffInvitation(
                    author: $request->user(),
                    staff: $member,
                )->execute();

                $sent++;
            } catch (InvalidArgumentException|ValidationException) {
                continue;
            }
        }

        return redirect()->route('dashboard.index')
            ->with('status', trans_choice(':count invitation is on its way.|:count invitations are on their way.', $sent));
    }
}
