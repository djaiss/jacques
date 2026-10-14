<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a company. Only one of its owners may do so.
 */
class DestroyCompany
{
    public function __construct(
        private readonly User $user,
        private readonly Company $company,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
    }

    private function validate(): void
    {
        if ($this->user->company_id !== $this->company->id) {
            throw new ModelNotFoundException('Company not found');
        }

        if (! $this->user->isOwner()) {
            throw new ModelNotFoundException('Company not found');
        }
    }

    private function destroy(): void
    {
        $this->company->delete();
    }
}
