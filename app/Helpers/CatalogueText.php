<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;

class CatalogueText
{
    /**
     * @param  array<string, string|null>  $texts
     * @param  list<string>  $languages
     */
    public static function resolve(array $texts, array $languages, string $locale): string
    {
        $language = Str::before($locale, '_');

        if (in_array($language, $languages, true) && ($texts[$language] ?? '') !== '') {
            return (string) $texts[$language];
        }

        foreach ($languages as $language) {
            if (($texts[$language] ?? '') !== '') {
                return (string) $texts[$language];
            }
        }

        return '';
    }

    /**
     * @param  array<string, string|null>  $texts
     * @param  list<string>  $languages
     * @return list<string>
     */
    public static function missingLanguages(array $texts, array $languages): array
    {
        return array_values(array_filter(
            $languages,
            fn (string $language): bool => ($texts[$language] ?? '') === '',
        ));
    }
}
