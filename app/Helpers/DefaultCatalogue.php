<?php

declare(strict_types=1);

namespace App\Helpers;

use Closure;
use Illuminate\Support\Str;

class DefaultCatalogue
{
    /** @return list<array{name: array<string, string>, services: list<array{name: array<string, string>, price: int, steps: list<array{name: array<string, string>|null, duration: int, requires_staff: bool}>}>}> */
    public static function categories(): array
    {
        return [
            [
                'name' => self::texts(fn (string $locale): string => __('Haircuts', locale: $locale)),
                'services' => [
                    self::service(fn (string $locale): string => __("Men's Haircut", locale: $locale), 3500, 30),
                    self::service(fn (string $locale): string => __("Women's Haircut", locale: $locale), 6500, 60),
                    self::service(fn (string $locale): string => __("Kids' Haircut", locale: $locale), 2500, 30),
                ],
            ],
            [
                'name' => self::texts(fn (string $locale): string => __('Hair Coloring', locale: $locale)),
                'services' => [
                    self::service(fn (string $locale): string => __('Full Color', locale: $locale), 10000, steps: [
                        self::step(fn (string $locale): string => __('Color application', locale: $locale), 30, true),
                        self::step(fn (string $locale): string => __('Processing', locale: $locale), 30, false),
                        self::step(fn (string $locale): string => __('Rinse & finish', locale: $locale), 30, true),
                    ]),
                    self::service(fn (string $locale): string => __('Root Touch-up', locale: $locale), 8500, 90),
                    self::service(fn (string $locale): string => __('Balayage', locale: $locale), 18000, 150),
                    self::service(fn (string $locale): string => __('Highlights', locale: $locale), 14000, steps: [
                        self::step(fn (string $locale): string => __('Application', locale: $locale), 45, true),
                        self::step(fn (string $locale): string => __('Processing', locale: $locale), 30, false),
                        self::step(fn (string $locale): string => __('Rinse & finish', locale: $locale), 30, true),
                    ]),
                ],
            ],
            [
                'name' => self::texts(fn (string $locale): string => __('Hair Styling', locale: $locale)),
                'services' => [
                    self::service(fn (string $locale): string => __('Blowout', locale: $locale), 4500, 45),
                    self::service(fn (string $locale): string => __('Updo', locale: $locale), 7500, 60),
                    self::service(fn (string $locale): string => __('Hair Straightening', locale: $locale), 10000, 90),
                ],
            ],
            [
                'name' => self::texts(fn (string $locale): string => __('Hair Treatments', locale: $locale)),
                'services' => [
                    self::service(fn (string $locale): string => __('Deep Conditioning', locale: $locale), 4000, steps: [
                        self::step(fn (string $locale): string => __('Application', locale: $locale), 10, true),
                        self::step(fn (string $locale): string => __('Processing', locale: $locale), 15, false),
                        self::step(fn (string $locale): string => __('Rinse & finish', locale: $locale), 10, true),
                    ]),
                    self::service(fn (string $locale): string => __('Keratin Treatment', locale: $locale), 15000, 120),
                    self::service(fn (string $locale): string => __('Scalp Treatment', locale: $locale), 5500, 45),
                ],
            ],
            [
                'name' => self::texts(fn (string $locale): string => __('Barbering', locale: $locale)),
                'services' => [
                    self::service(fn (string $locale): string => __('Beard Trim', locale: $locale), 2000, 20),
                    self::service(fn (string $locale): string => __('Traditional Shave', locale: $locale), 3500, 30),
                    self::service(fn (string $locale): string => __('Hair & Beard', locale: $locale), 5000, 50),
                ],
            ],
            [
                'name' => self::texts(fn (string $locale): string => __('Other Services', locale: $locale)),
                'services' => [
                    self::service(fn (string $locale): string => __('Consultation', locale: $locale), 0, 15),
                ],
            ],
        ];
    }

    /**
     * Write a text in every interface locale, keyed by language code. The text
     * is asked for through a literal `__()` call so that `jacques:localize`
     * finds it.
     *
     * @param  Closure(string): string  $text
     * @return array<string, string>
     */
    public static function texts(Closure $text): array
    {
        $texts = [];

        foreach (array_keys(config('jacques.locales')) as $locale) {
            $texts[Str::before($locale, '_')] = $text($locale);
        }

        return $texts;
    }

    /**
     * @param  Closure(string): string  $name
     * @param  list<array{name: array<string, string>|null, duration: int, requires_staff: bool}>  $steps
     * @return array{name: array<string, string>, price: int, steps: list<array{name: array<string, string>|null, duration: int, requires_staff: bool}>}
     */
    private static function service(Closure $name, int $price, int $duration = 0, array $steps = []): array
    {
        return [
            'name' => self::texts($name),
            'price' => $price,
            'steps' => $steps === [] ? [['name' => null, 'duration' => $duration, 'requires_staff' => true]] : $steps,
        ];
    }

    /**
     * @param  Closure(string): string  $name
     * @return array{name: array<string, string>, duration: int, requires_staff: bool}
     */
    private static function step(Closure $name, int $duration, bool $requiresStaff): array
    {
        return [
            'name' => self::texts($name),
            'duration' => $duration,
            'requires_staff' => $requiresStaff,
        ];
    }
}
