<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use Override;

class TeamNoAccountViewModel extends TeamViewModel
{
    #[Override]
    protected function tab(): ?string
    {
        return 'no_account';
    }
}
