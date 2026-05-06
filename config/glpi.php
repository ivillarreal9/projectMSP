<?php

return [
    'base_url'   => env('GLPI_BASE_URL'),
    'app_token'  => env('GLPI_APP_TOKEN'),
    'user_token' => env('GLPI_USER_TOKEN'),

    'asset_types' => [
        'Computer'         => 'Computadoras',
        'NetworkEquipment' => 'Equipos de Red',
        'Printer'          => 'Impresoras',
        'Phone'            => 'Teléfonos',
        'Monitor'          => 'Monitores',
        'Peripheral'       => 'Periféricos',
        'Software'         => 'Software',
    ],
];