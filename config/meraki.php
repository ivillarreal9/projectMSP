<?php

return [
    'api_key'       => env('MERAKI_API_KEY'),
    'base_url'      => env('MERAKI_BASE_URL', 'https://api.meraki.com/api/v1'),
    'notify_emails' => env('MERAKI_NOTIFY_EMAILS', ''),

    'device_types' => [
        'AP'      => 'Access Points',
        'MX'      => 'Firewalls / Security',
        'MS'      => 'Switches',
        'MG'      => 'Cellular Gateways',
        'MV'      => 'Cameras',
        'MT'      => 'Sensors',
    ],
];
