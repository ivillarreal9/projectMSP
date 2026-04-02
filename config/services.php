<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
        'url'   => 'https://api.anthropic.com/v1',
    ],

    'odoo' => [
        'url'      => env('ODOO_URL'),
        'db'       => env('ODOO_DB'),
        'username' => env('ODOO_USERNAME'),
        'api_key'  => env('ODOO_API_KEY'),
    ],

    'sharepoint' => [
    'tenant_id'     => env('AZURE_TENANT_ID'),
    'client_id'     => env('AZURE_CLIENT_ID'),
    'client_secret' => env('AZURE_CLIENT_SECRET'),
    'site_url'      => env('SHAREPOINT_SITE_URL'),
    'folder'        => env('SHAREPOINT_FOLDER'),
    'file'          => env('SHAREPOINT_FILE'),
    'folder_id'     => env('SHAREPOINT_FOLDER_ID'),  // ← agregar

],

];
