<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seller
    |--------------------------------------------------------------------------
    |
    | Your company, used by AlgeriaInvoice::make() so you do not repeat it on
    | every invoice. Keys: name, legal_form, capital, address, phone, email,
    | rc, nif, nis, ai, rib.
    |
    */

    'seller' => [
        // 'name' => 'SARL Exemple',
        // 'legal_form' => 'SARL',
        // 'capital' => 1000000,
        // 'address' => '12 rue Didouche Mourad, Alger',
        // 'rc' => '16/00-1234567B21',
        // 'nif' => '000116123456789',
        // 'nis' => '000116010203040',
        // 'ai' => '16012345678',
    ],

    /*
    |--------------------------------------------------------------------------
    | VAT rates (%)
    |--------------------------------------------------------------------------
    |
    | 19 = standard rate, 9 = reduced rate, 0 = exempt (exonéré).
    |
    */

    'vat_rates' => [19, 9, 0],

    /*
    |--------------------------------------------------------------------------
    | Stamp duty on cash payments (droit de timbre, Code du timbre art. 100-I)
    |--------------------------------------------------------------------------
    |
    | Defaults follow the Finance Law 2025 (art. 46): 1 DA per 100 DA up to
    | 30,000 DA, 1.5 DA per 100 DA up to 100,000 DA, 2 DA per 100 DA above,
    | without cap. Electronic payments are exempt (art. 258 quinquies).
    |
    | Check these points with your accountant, the sources disagree:
    |  - mode: 'whole' applies the bracket rate to the whole amount,
    |          'marginal' applies each rate only to its own bracket;
    |  - exempt_up_to: amounts up to this value (DA) pay no stamp duty;
    |  - minimum: 5 DA under art. 100, the general minimum of art. 2 is 30 DA.
    |
    */

    'stamp_duty' => [
        'base' => 'ttc',            // 'ttc' or 'ht'
        'mode' => 'whole',          // 'whole' or 'marginal'
        'slice' => 100,             // DA, every started slice counts
        'exempt_up_to' => 300,      // DA
        'brackets' => [             // [up to (DA, null = no limit), DA per slice]
            [30000, 1.0],
            [100000, 1.5],
            [null, 2.0],
        ],
        'minimum' => 5,             // DA
        'maximum' => null,          // DA, null = no cap
    ],

    /*
    |--------------------------------------------------------------------------
    | Numbering
    |--------------------------------------------------------------------------
    |
    | Gap-free sequences, restarted every year. Placeholders: {series},
    | {year}, {number} (padded to 'pad' digits).
    |
    */

    'numbering' => [
        'table' => 'invoice_sequences',
        'format' => '{series}-{year}-{number}',
        'pad' => 5,
        'series' => [
            'invoice' => 'FA',
            'credit_note' => 'AV',
            'proforma' => 'PF',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal forms that must show their share capital
    |--------------------------------------------------------------------------
    */

    'company_forms' => ['SARL', 'EURL', 'SPA', 'SNC', 'SCS', 'SCA', 'EPE', 'EPIC', 'SPAS', 'SPASU'],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    | Built-in: 'classic', 'modern', 'minimal'. Add your own as name => view,
    | for example 'shop' => 'invoices.shop' (resources/views/invoices/shop.blade.php).
    | Choose per call: AlgeriaInvoice::pdf($invoice, 'ar', template: 'modern').
    |
    */

    'template' => 'classic',

    'templates' => [
        // 'shop' => 'invoices.shop',
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF (requires mpdf/mpdf)
    |--------------------------------------------------------------------------
    */

    'pdf' => [
        'format' => 'A4',
        'temp_dir' => null,         // null = storage_path('app/algeria-invoice-tmp')
    ],

];
