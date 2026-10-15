<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use InvalidArgumentException;

/**
 * Set the languages a salon writes its catalogue in, in the order given, the
 * first one being the language a text falls back to. Texts written in a
 * language that is removed stay saved, so adding it back brings them back.
 */
class UpdateCompanyLanguages
{
    /** @param  list<string>  $languages */
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private readonly array $languages,
    ) {}

    public function execute(): Company
    {
        $this->authorize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->company;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->languages === []) {
            throw new InvalidArgumentException('A company needs at least one language');
        }

        foreach ($this->languages as $language) {
            if (! array_key_exists($language, config('jacques.content_languages'))) {
                throw new InvalidArgumentException("{$language} is not a language the catalogue can be written in");
            }
        }

        if (count(array_unique($this->languages)) !== count($this->languages)) {
            throw new InvalidArgumentException('A language can only be listed once');
        }
    }

    private function update(): void
    {
        $this->company->languages = array_values($this->languages);
        $this->company->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::CompanyLanguagesUpdated,
            parameters: ['languages' => implode(', ', $this->languages)],
        )->onQueue('low');
    }
}
