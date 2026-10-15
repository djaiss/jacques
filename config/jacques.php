<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The languages the interface is translated into. The key is the locale, and
    | matches the name of the file in the lang folder. The flag is a css value
    | for the background property, drawn rather than shipped as an image.
    |
    */

    'locales' => [
        'en' => [
            'label' => 'English',
            'region' => 'United Kingdom',
            'flag' => 'linear-gradient(to bottom, transparent 40%, #C8102E 40% 60%, transparent 60%), linear-gradient(to right, transparent 38%, #C8102E 38% 62%, transparent 62%), linear-gradient(to bottom, transparent 30%, #fff 30% 70%, transparent 70%), linear-gradient(to right, transparent 28%, #fff 28% 72%, transparent 72%), linear-gradient(to bottom right, transparent 44%, #fff 44% 56%, transparent 56%), linear-gradient(to bottom left, transparent 44%, #fff 44% 56%, transparent 56%), #012169',
        ],
        'fr_FR' => [
            'label' => 'Français',
            'region' => 'France',
            'flag' => 'linear-gradient(to right, #002395 0 33%, #fff 33% 66%, #ed2939 66%)',
        ],
        'de_DE' => [
            'label' => 'Deutsch',
            'region' => 'Deutschland',
            'flag' => 'linear-gradient(to bottom, #000 0 33%, #dd0000 33% 66%, #ffce00 66%)',
        ],
        'es_ES' => [
            'label' => 'Español',
            'region' => 'España',
            'flag' => 'linear-gradient(to bottom, #aa151b 0 25%, #f1bf00 25% 75%, #aa151b 75%)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalogue languages
    |--------------------------------------------------------------------------
    |
    | The languages a salon can write its catalogue in. They are not the
    | interface locales above: a salon may write in Portuguese while the
    | interface is not translated into it. The key is an ISO 639-1 code, and
    | the label is the name of the language in that language.
    |
    */

    'content_languages' => [
        'en' => [
            'label' => 'English',
            'flag' => 'linear-gradient(to bottom, transparent 40%, #C8102E 40% 60%, transparent 60%), linear-gradient(to right, transparent 38%, #C8102E 38% 62%, transparent 62%), linear-gradient(to bottom, transparent 30%, #fff 30% 70%, transparent 70%), linear-gradient(to right, transparent 28%, #fff 28% 72%, transparent 72%), linear-gradient(to bottom right, transparent 44%, #fff 44% 56%, transparent 56%), linear-gradient(to bottom left, transparent 44%, #fff 44% 56%, transparent 56%), #012169',
        ],
        'fr' => [
            'label' => 'Français',
            'flag' => 'linear-gradient(to right, #002395 0 33%, #fff 33% 66%, #ed2939 66%)',
        ],
        'es' => [
            'label' => 'Español',
            'flag' => 'linear-gradient(to bottom, #aa151b 0 25%, #f1bf00 25% 75%, #aa151b 75%)',
        ],
        'pt' => [
            'label' => 'Português',
            'flag' => 'radial-gradient(circle at 40% 50%, #ffe900 0 18%, transparent 19%), linear-gradient(to right, #046a38 0 40%, #da291c 40%)',
        ],
        'it' => [
            'label' => 'Italiano',
            'flag' => 'linear-gradient(to right, #009246 0 33%, #fff 33% 66%, #ce2b37 66%)',
        ],
        'de' => [
            'label' => 'Deutsch',
            'flag' => 'linear-gradient(to bottom, #000 0 33%, #dd0000 33% 66%, #ffce00 66%)',
        ],
        'ar' => [
            'label' => 'العربية',
            'flag' => 'radial-gradient(circle at 50% 50%, #006233 0 16%, transparent 17%), #c1272d',
        ],
        'zh' => [
            'label' => '中文',
            'flag' => 'radial-gradient(circle at 22% 32%, #ffde00 0 14%, transparent 15%), #de2910',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Languages of a country
    |--------------------------------------------------------------------------
    |
    | The catalogue languages chosen for a salon as soon as its country is
    | picked during the onboarding. The owner can change them right away. A
    | country missing from this list starts with English alone.
    |
    */

    'country_languages' => [
        'BE' => ['fr'],
        'CA' => ['en', 'fr'],
        'CH' => ['fr', 'de'],
        'FR' => ['fr'],
        'US' => ['en'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currencies
    |--------------------------------------------------------------------------
    |
    | The currencies a salon can price its catalogue in, by ISO 4217 code.
    | Every one of them has two decimals, so every amount is stored in cents.
    |
    */

    'currencies' => ['CAD', 'USD', 'EUR', 'GBP', 'CHF'],

    /*
    |--------------------------------------------------------------------------
    | Currency of a country
    |--------------------------------------------------------------------------
    |
    | The currency chosen for a salon as soon as its country is picked during
    | the onboarding. The owner can change it right away. A country missing
    | from this list starts with the Canadian dollar.
    |
    */

    'country_currencies' => [
        'BE' => 'EUR',
        'CA' => 'CAD',
        'CH' => 'CHF',
        'FR' => 'EUR',
        'US' => 'USD',
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember me
    |--------------------------------------------------------------------------
    |
    | How long a session survives when somebody ticks "remember me" on the sign
    | in screen. The screen names this number, so changing it here changes what
    | the visitor is promised.
    |
    */

    'remember_duration_days' => env('JACQUES_REMEMBER_DURATION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Magic link
    |--------------------------------------------------------------------------
    |
    | How many minutes a passwordless sign in link stays valid. It is short on
    | purpose: the link is single use, but a short window limits the damage of
    | a forwarded or intercepted email.
    |
    */

    'magic_link_duration_minutes' => env('JACQUES_MAGIC_LINK_DURATION_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Legal pages
    |--------------------------------------------------------------------------
    |
    | Where the terms of use and the privacy policy live. They sit on the public
    | site rather than in the application, so an instance that runs its own copy
    | of those documents can point at them here.
    |
    */

    'terms_url' => env('JACQUES_TERMS_URL', 'https://jacques.io/terms'),

    'privacy_url' => env('JACQUES_PRIVACY_URL', 'https://jacques.io/privacy'),

    /*
    |--------------------------------------------------------------------------
    | Seeded account
    |--------------------------------------------------------------------------
    |
    | Who the seeder creates the company around, and the password it gives them.
    | The sign in shortcut on a machine somebody develops on reads the same two
    | values, so there is a single place that says who the default account is.
    |
    */

    'seed_email' => env('JACQUES_SEED_EMAIL', 'michael.scott@dundermifflin.com'),

    'seed_password' => env('JACQUES_SEED_PASSWORD', 'password'),

];
