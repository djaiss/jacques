<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use Override;

class TeamInvitedViewModel extends TeamViewModel
{
    #[Override]
    protected function tab(): ?string
    {
        return 'invited';
    }
}
