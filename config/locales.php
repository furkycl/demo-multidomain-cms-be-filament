<?php

declare(strict_types=1);

/**
 * 10 desteklenen dil. Tüm lead'ler tek CRM'e (Omnigos) gider.
 * crm_target field'ı ileride farklı CRM'ler eklenirse override için kalıyor.
 */

return [
    'default' => env('DEFAULT_LOCALE', 'tr'),

    'supported' => [
        'tr' => ['name' => 'Turkish',    'native_name' => 'Türkçe',       'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'en' => ['name' => 'English',    'native_name' => 'English',      'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'ar' => ['name' => 'Arabic',     'native_name' => 'العربية',       'direction' => 'rtl', 'crm_target' => 'omnigos'],
        'fr' => ['name' => 'French',     'native_name' => 'Français',     'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'es' => ['name' => 'Spanish',    'native_name' => 'Español',      'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'pt' => ['name' => 'Portuguese', 'native_name' => 'Português',    'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'ko' => ['name' => 'Korean',     'native_name' => '한국어',         'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'ja' => ['name' => 'Japanese',   'native_name' => '日本語',         'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'it' => ['name' => 'Italian',    'native_name' => 'Italiano',     'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'de' => ['name' => 'German',     'native_name' => 'Deutsch',      'direction' => 'ltr', 'crm_target' => 'omnigos'],
    ],
];
