<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateDefaultCatalogue;
use App\Models\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Copy the default catalogue into a company that just signed up. It runs on the
 * queue so signing up stays fast, and it must finish before the owner reaches
 * the services step of the onboarding.
 */
class CreateDefaultServices implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $companyId,
    ) {}

    public function handle(): void
    {
        $company = Company::query()->findOrFail($this->companyId);

        new CreateDefaultCatalogue(company: $company)->execute();
    }
}
