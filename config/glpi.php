<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GLPI API Configuration
    |--------------------------------------------------------------------------
    */

    'base_url'   => env('GLPI_BASE_URL'),
    'app_token'  => env('GLPI_APP_TOKEN'),
    'user_token' => env('GLPI_USER_TOKEN'),

    /*
    | Tipos de activos soportados
    | Clave => nombre visible en la UI
    */
    'asset_types' => [
        'Computer'        => 'Computadoras',
        'NetworkEquipment'=> 'Equipos de Red',
        'Printer'         => 'Impresoras',
        'Phone'           => 'Teléfonos',
        'Monitor'         => 'Monitores',
        'Peripheral'      => 'Periféricos',
        'Software'        => 'Software',
    ],
];
