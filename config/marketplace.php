<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commission Settings
    |--------------------------------------------------------------------------
    */
    'commission' => [
        'default_rate' => env('COMMISSION_DEFAULT_RATE', 0.10), // 10%
        'minimum_payout' => env('COMMISSION_MINIMUM_PAYOUT', 100000), // IDR 100,000
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Settings (PMK 37/2025 Compliance)
    |--------------------------------------------------------------------------
    */
    'tax' => [
        'vat_rate' => env('TAX_VAT_RATE', 0.11), // 11% VAT
        'marketplace_withholding_rate' => env('TAX_MARKETPLACE_WITHHOLDING_RATE', 0.025), // 2.5%
        'report_frequency' => env('TAX_REPORT_FREQUENCY', 'monthly'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vendor Settings
    |--------------------------------------------------------------------------
    */
    'vendor' => [
        'auto_approve' => env('VENDOR_AUTO_APPROVE', false),
        'product_auto_publish' => env('VENDOR_PRODUCT_AUTO_PUBLISH', false),
        'min_balance_for_payout' => env('VENDOR_MIN_BALANCE_FOR_PAYOUT', 100000),
        'payout_schedule' => env('VENDOR_PAYOUT_SCHEDULE', 'weekly'), // weekly, monthly
        'require_npwp' => true, // Always required for PMK 37/2025
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping Settings
    |--------------------------------------------------------------------------
    */
    'shipping' => [
        'default_provider' => env('SHIPPING_DEFAULT_PROVIDER', 'rajaongkir'),
        'allow_vendor_custom_rates' => env('SHIPPING_ALLOW_VENDOR_CUSTOM_RATES', true),
        'cache_ttl' => env('RAJAONGKIR_CACHE_TTL', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Settings
    |--------------------------------------------------------------------------
    */
    'order' => [
        'number_prefix' => 'MV',
        'auto_complete_days' => 7, // Auto-complete after 7 days of shipment
        'cancellation_window_minutes' => 30, // User can cancel within 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Settings
    |--------------------------------------------------------------------------
    */
    'media' => [
        'max_upload_size' => env('IMAGE_MAX_SIZE', 5120), // 5MB in KB
        'auto_webp_conversion' => env('IMAGE_AUTO_WEBP_CONVERSION', true),
        'quality' => env('IMAGE_QUALITY', 85),
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'product_catalog_ttl' => env('CACHE_TTL_PRODUCT_CATALOG', 3600),
        'vendor_data_ttl' => env('CACHE_TTL_VENDOR_DATA', 1800),
        'shipping_rates_ttl' => env('CACHE_TTL_SHIPPING_RATES', 86400),
        'tax_rates_ttl' => env('CACHE_TTL_TAX_RATES', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'api_per_minute' => env('RATE_LIMIT_API_PER_MINUTE', 60),
        'checkout_per_minute' => env('RATE_LIMIT_CHECKOUT_PER_MINUTE', 5),
        'vendor_upload_per_minute' => env('RATE_LIMIT_VENDOR_UPLOAD_PER_MINUTE', 20),
    ],
];
