<?php

declare(strict_types=1);

/**
 * Brand registry. Site.brand bu listenin key'lerinden birini tutar.
 *
 * Marka başına outbound link whitelist — frontend bu listeyi /api/site/...
 * üzerinden çekip nav/footer'da yalnızca bu domain'lere link verecek.
 * "Cross-link engelleme" politikası buradan beslenir.
 */

return [
    'kaplan' => [
        'name' => 'Kaplan International Languages',
        'parent_domain' => 'kaplaninternational.com',
        'outbound_whitelist' => [
            'topstudy.com',
            'coursefinders.com',
        ],
    ],
    'alpadia' => [
        'name' => 'Alpadia Language Schools',
        'parent_domain' => 'alpadia.com',
        'outbound_whitelist' => [
            'linguland.com',
            'coursefinders.com',
        ],
    ],
    'azurlingua' => [
        'name' => 'Azurlingua',
        'parent_domain' => 'azurlingua.com',
        'outbound_whitelist' => [
            'linguland.com',
            'coursefinders.com',
        ],
    ],
];
