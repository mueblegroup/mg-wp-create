<?php

return [

    'base_domain' => env('SAAS_BASE_DOMAIN', 'sites.mueble-playground.cc'),

    'plans' => [
        'bronze' => [
            'label' => 'Bronze',
            'price' => 30.00,
            'currency' => 'MYR',
            'allows_custom_domain' => false,
            'max_themes' => 10,
            'resource_profile' => 'bronze',
            'sort_order' => 1,
        ],
        'silver' => [
            'label' => 'Silver',
            'price' => 70.00,
            'currency' => 'MYR',
            'allows_custom_domain' => true,
            'max_themes' => 20,
            'resource_profile' => 'silver',
            'sort_order' => 2,
        ],
        'gold' => [
            'label' => 'Gold',
            'price' => 110.00,
            'currency' => 'MYR',
            'allows_custom_domain' => true,
            'max_themes' => 999,
            'resource_profile' => 'gold',
            'sort_order' => 3,
        ],
    ],

];