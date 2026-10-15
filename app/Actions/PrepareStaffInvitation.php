<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Prepare the invitation for a staff member to create an account holding a
 * role, or change the role of the one already prepared. Nothing is sent:
 * sending belongs to a later feature. Only somebody who manages roles may do
 * it, since the invitation hands one out.
 */
class PrepareStaffInvitation
{
    private StaffInvitation $invitation;

    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly Role $role,
    ) {}

    public function execute(): StaffInvitation
    {
        $this->authorize();
        $this->validate();
        $this->prepare();
        $this->log();

        return $this->invitation;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->staff->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->role->company_id !== $this->staff->company_id) {
            throw new ModelNotFoundException('Role not found');
        }

        if ($this->staff->email === null) {
            throw new InvalidArgumentException('A staff member without an email address cannot be invited');
        }

        if ($this->staff->user_id !== null) {
            throw new InvalidArgumentException('The staff member already has an account');
        }
    }

    private function prepare(): void
    {
        $this->invitation = StaffInvitation::query()->updateOrCreate(
            ['staff_id' => $this->staff->id],
            ['role_id' => $this->role->id, 'author_id' => $this->author->id],
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffInvitationPrepared,
            parameters: [
                'name' => $this->staff->name,
                'role' => $this->role->name,
            ],
        )->onQueue('low');
    }
}
