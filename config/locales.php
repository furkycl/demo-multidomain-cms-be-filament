<?php

declare(strict_types=1);

/**
 * multi-cms desteklenen lokal listesi.
 * Ekleme/çıkarma ihtiyacında tek nokta — frontend de buradan beslenir (API exposes it).
 *
 * Her locale'in:
 *   code         : ISO 639-1 (URL'de geçer, /tr, /en …)
 *   name         : İngilizce ad (admin UI'da kolay tarama)
 *   native_name  : kendi dilindeki adı (front-end language switcher)
 *   direction    : ltr / rtl
 *   crm_target   : 'omnigos' | 'linguland' (lead routing)
 */

return [
    'default' => env('DEFAULT_LOCALE', 'tr'),

    'supported' => [
        'tr' => ['name' => 'Turkish',    'native_name' => 'Türkçe',       'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'en' => ['name' => 'English',    'native_name' => 'English',      'direction' => 'ltr', 'crm_target' => 'omnigos'],
        'ar' => ['name' => 'Arabic',     'native_name' => 'العربية',       'direction' => 'rtl', 'crm_target' => 'omnigos'],
        'fr' => ['name' => 'French',     'native_name' => 'Français',     'direction' => 'ltr', 'crm_target' => 'linguland'],
        'es' => ['name' => 'Spanish',    'native_name' => 'Español',      'direction' => 'ltr', 'crm_target' => 'linguland'],
        'pt' => ['name' => 'Portuguese', 'native_name' => 'Português',    'direction' => 'ltr', 'crm_target' => 'linguland'],
        'ko' => ['name' => 'Korean',     'native_name' => '한국어',         'direction' => 'ltr', 'crm_target' => 'linguland'],
        'ja' => ['name' => 'Japanese',   'native_name' => '日本語',         'direction' => 'ltr', 'crm_target' => 'linguland'],
        'it' => ['name' => 'Italian',    'native_name' => 'Italiano',     'direction' => 'ltr', 'crm_target' => 'linguland'],
        'de' => ['name' => 'German',     'native_name' => 'Deutsch',      'direction' => 'ltr', 'crm_target' => 'linguland'],
    ],
];
