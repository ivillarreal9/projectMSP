<?php

/**
 * Módulos disponibles en el sistema.
 *
 * Para agregar un nuevo módulo:
 * 1. Añade la entrada aquí con su slug como clave
 * 2. Agrega la ruta con middleware('module:tu_slug') en routes/web.php
 * El módulo aparecerá automáticamente en la gestión de roles.
 */

return [
    'msp_reports' => [
        'nombre'      => 'MSP Reports',
        'descripcion' => 'Reportes y correos',
        'icon'        => 'fa-file-chart-column',
        'color'       => '#e8610a',
        'bg'          => '#fff3e8',
        'light_color' => '#c2410c',
        'light_bg'    => '#fff3e8',
        'dark_color'  => '#fb923c',
        'dark_bg'     => 'rgba(232,97,10,.15)',
    ],

    'api_msp' => [
        'nombre'      => 'API MSP',
        'descripcion' => 'Consulta de la API',
        'icon'        => 'fa-code',
        'color'       => '#7c3aed',
        'bg'          => '#f0edfe',
        'light_color' => '#6d28d9',
        'light_bg'    => '#f0edfe',
        'dark_color'  => '#a78bfa',
        'dark_bg'     => 'rgba(124,58,237,.15)',
    ],

    'meta2' => [
        'nombre'      => 'META 2',
        'descripcion' => 'Metas y objetivos',
        'icon'        => 'fa-bolt',
        'color'       => '#059669',
        'bg'          => '#ecfdf5',
        'light_color' => '#065f46',
        'light_bg'    => '#d1fae5',
        'dark_color'  => '#34d399',
        'dark_bg'     => 'rgba(5,150,105,.15)',
    ],

    'encuestas' => [
        'nombre'      => 'Encuestas',
        'descripcion' => 'Satisfacción clientes',
        'icon'        => 'fa-clipboard-list',
        'color'       => '#2563eb',
        'bg'          => '#eff6ff',
        'light_color' => '#1d4ed8',
        'light_bg'    => '#dbeafe',
        'dark_color'  => '#60a5fa',
        'dark_bg'     => 'rgba(37,99,235,.15)',
    ],

    'usuarios' => [
        'nombre'      => 'Usuarios',
        'descripcion' => 'Gestión de accesos',
        'icon'        => 'fa-users',
        'color'       => '#a21caf',
        'bg'          => '#fdf4ff',
        'light_color' => '#86198f',
        'light_bg'    => '#fdf4ff',
        'dark_color'  => '#e879f9',
        'dark_bg'     => 'rgba(162,28,175,.15)',
    ],

    'glpi' => [
        'nombre'      => 'GLPI',
        'descripcion' => 'Inventario de activos',
        'icon'        => 'fa-server',
        'color'       => '#0891b2',
        'bg'          => '#ecfeff',
        'light_color' => '#0e7490',
        'light_bg'    => '#cffafe',
        'dark_color'  => '#22d3ee',
        'dark_bg'     => 'rgba(8,145,178,.15)',
    ],

    'sales' => [
        'nombre'      => 'Sales',
        'descripcion' => 'Dashboard de ventas',
        'icon'        => 'fa-chart-line',
        'color'       => '#0d9488',
        'bg'          => '#f0fdfa',
        'light_color' => '#0f766e',
        'light_bg'    => '#ccfbf1',
        'dark_color'  => '#2dd4bf',
        'dark_bg'     => 'rgba(13,148,136,.15)',
    ],

    'meraki' => [
        'nombre'      => 'Meraki',
        'descripcion' => 'Redes y dispositivos Cisco',
        'icon'        => 'fa-wifi',
        'color'       => '#0f766e',
        'bg'          => '#f0fdfa',
        'light_color' => '#0d9488',
        'light_bg'    => '#ccfbf1',
        'dark_color'  => '#2dd4bf',
        'dark_bg'     => 'rgba(15,118,110,.15)',
    ],
];
