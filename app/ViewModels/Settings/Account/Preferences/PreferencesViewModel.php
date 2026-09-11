<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Preferences;

use App\Enums\TimeFormatEnum;
use App\Models\User;

class PreferencesViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function locales(): array
    {
        $current = $this->locale();

        return collect(config('jacques.locales'))
            ->map(fn (array $locale, string $code): array => [
                'value' => $code,
                'label' => $locale['label'],
                'hint' => $locale['region'],
                'selected' => $code === $current,
            ])
            ->values()
            ->all();
    }

    public function localeLabel(): string
    {
        return config('jacques.locales')[$this->locale()]['label'];
    }

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function timeFormats(): array
    {
        return collect(TimeFormatEnum::cases())
            ->map(fn (TimeFormatEnum $format): array => [
                'value' => $format->value,
                'label' => __($format->label()),
                'hint' => $format->example(),
                'selected' => $format === $this->timeFormat(),
            ])
            ->all();
    }

    public function timeFormatLabel(): string
    {
        return __($this->timeFormat()->label());
    }

    public function timePreview(): string
    {
        return $this->timeFormat()->format(now());
    }

    public function locale(): string
    {
        $locale = app()->getLocale();

        return array_key_exists($locale, config('jacques.locales'))
            ? $locale
            : config('app.locale');
    }

    public function timeFormat(): TimeFormatEnum
    {
        return $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;
    }
}
